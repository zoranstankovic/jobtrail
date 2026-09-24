# Deploying JobTrail to a private server

This guide runs JobTrail on a small Linux server that only you can reach. There is no public website and no login page: the server joins your private [Tailscale](https://tailscale.com) network, and only your own devices can open the app. The database is backed up every night, encrypted, to object storage, and a monthly restore test proves the backups work.

Every step says **what** to do, **why**, and how to **check** it. Commands with `sudo` run on the server; commands without a prompt note run on your Mac in the JobTrail checkout.

Placeholders: `<user>` is your account on the server, `<public-ip>` the server's public IPv4 address, `<tailnet>` your tailnet's DNS name (for example `tail1a2b3c.ts.net`), `<region>` an object storage region, `<bucket>` the bucket name, `<check-uuid>` the healthchecks.io check.

## 1. How it fits together

```
Mac (Tailscale) ── https://jobtrail.<tailnet> ──▶ server (Debian 13)
                                                  │ tailscale serve: HTTPS certificate, ends TLS
                                                  ▼
                                   127.0.0.1:8080 → web (nginx) → app (PHP-FPM) → db (PostgreSQL 18)
                                                    └──── Docker network; nothing on the public IP ────┘
```

- **Images:** GitHub Actions builds two images for every commit on `main` that passes the checks: `ghcr.io/zoranstankovic/jobtrail-app:<commit sha>` (PHP with the code and the built assets) and `ghcr.io/zoranstankovic/jobtrail-web:<commit sha>` (nginx with `public/`). The server never builds anything.
- **Deploying:** `make deploy` on the Mac tells the server which commit to run (§4).
- **Public surface:** none. Tailscale needs no open port, SSH is only reachable over Tailscale, and nginx listens on the loopback interface only.
- **Data:** the only state on the server is the database volume and the settings in `/opt/jobtrail`. Both are in the nightly backup (§5).

Files in `/opt/jobtrail` on the server:

| File | Read by | Content |
|---|---|---|
| `compose.prod.yaml` | Docker Compose | The stack; copied by `make deploy` |
| `.env` | Docker Compose only (variable substitution) | `COMPOSE_FILE=compose.prod.yaml` and `TAG=<commit sha>`; written by `make deploy` |
| `app.env` | Laravel, mounted as `/var/www/html/.env` | `APP_KEY`, `APP_URL`, the database password, … |
| `secrets/db_password` | PostgreSQL, as a Compose secret | The database password |
| `backup.sh` | the backup timer | Copied by `make deploy` |
| `backups/` | you, when a deploy went wrong | A dump taken before each deploy (newest 5) |

`/opt/jobtrail` and `secrets/` are readable by you and root only (mode `700`). The files inside are `644`, because the containers read them as their own users (`www-data` in `app`, `postgres` in `db`), not as you.

## 2. Prepare your Mac

**What:** an SSH key, Tailscale, two command-line tools, and a place for secrets.

1. **SSH key.** Check for one: `ls ~/.ssh/id_ed25519.pub`. If it is missing, create it with a passphrase and let macOS remember the passphrase:

   ```bash
   ssh-keygen -t ed25519 -C "<user>@mac"
   ssh-add --apple-use-keychain ~/.ssh/id_ed25519
   ```

   **Why:** the server will accept keys only, never passwords.

2. **Tailscale.** Install the macOS app from <https://tailscale.com/download> and sign in; this creates your tailnet. In the [admin console](https://login.tailscale.com/admin/dns), on the **DNS** page: keep the random tailnet name (`tail…ts.net`), turn on **MagicDNS**, and under **HTTPS Certificates** select **Enable HTTPS**.

   **Why:** MagicDNS gives every device a name (`jobtrail`), and HTTPS lets `tailscale serve` get a Let's Encrypt certificate for `jobtrail.<tailnet>`. Certificate names are published in public Certificate Transparency logs, which is why the tailnet name should say nothing about you.

3. **Tools:** `brew install restic awscli` — restic reads the backups (§6), the AWS CLI configures the bucket (§5.1).

4. **A password manager entry** "JobTrail server". It will collect: the public IP, your server password (for `sudo`), `app.env`, the database password, the restic password, both S3 key pairs and the healthchecks.io ping URL.

**Check:** `ssh-add -l` lists the key; the Tailscale menu shows *Connected*; `restic version` and `aws --version` print versions.

## 3. Set up the server

Order a VPS with **Debian 13 (trixie)** (JobTrail was set up on an OVHcloud VPS-1: 2 vCores, 4 GB RAM, 40 GB NVMe). If the order form accepts an SSH public key, paste the contents of `~/.ssh/id_ed25519.pub`.

### 3.1 First login

**What:** OVHcloud sends the address and a link to a temporary password for the user `debian`. Log in and change the password when asked; the session then closes.

```bash
ssh debian@<public-ip>
ssh debian@<public-ip>        # again, with the new password
grep VERSION_CODENAME /etc/os-release
```

**Check:** the last command prints `VERSION_CODENAME=trixie`.

### 3.2 Base system and your own account

**What:** update everything, name the machine, use UTC, and create your own account with `sudo`.

```bash
sudo apt update && sudo apt full-upgrade -y
sudo hostnamectl set-hostname jobtrail
sudo timedatectl set-timezone Etc/UTC
sudo adduser <user>                  # choose a strong password: it is your sudo password
sudo usermod -aG sudo <user>
```

If `sudo` now warns `unable to resolve host jobtrail`, add the name: `echo '127.0.1.1 jobtrail' | sudo tee -a /etc/hosts`.

On the Mac, install your key for the new account and log in with it:

```bash
ssh-copy-id -i ~/.ssh/id_ed25519.pub <user>@<public-ip>
ssh <user>@<public-ip>
sudo -v
```

Then lock the provider's default account, so only your account can log in:

```bash
sudo usermod --lock --expiredate 1 debian
sudo chage -l debian | grep 'Account expires'
```

**Why:** servers use UTC so logs and timers never jump with daylight saving time (the app has its own time zone). A personal account plus `sudo` leaves a trace of who did what; `debian` is a well-known name that bots try first.

**Check:** `hostname` prints `jobtrail`; `timedatectl` shows `Time zone: Etc/UTC`; `sudo -v` works; `chage` prints `Account expires : Jan 02, 1970`.

### 3.3 SSH: keys only, no root, only you

**What:** a drop-in file that overrides the defaults. Keep this SSH session open until the check at the end has passed in a **second** terminal — if something is wrong, the open session is your way back in.

```bash
sudo tee /etc/ssh/sshd_config.d/10-jobtrail.conf > /dev/null <<'EOF'
# Keys only, no root login, only <user> (docs/deployment.md §3.3).
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
AllowUsers <user>
EOF
sudo sshd -t && sudo systemctl reload ssh
sudo sshd -T | grep -E '^(passwordauthentication|kbdinteractiveauthentication|permitrootlogin|allowusers) '
```

**Why:** `sshd` reads the files in `sshd_config.d/` in name order and keeps the **first** value it finds, so `10-…` wins over a provider's `50-cloud-init.conf` that might allow passwords. `sshd -t` checks the syntax before the reload, so a typo cannot lock you out.

**Check:** `sshd -T` prints `passwordauthentication no`, `kbdinteractiveauthentication no`, `permitrootlogin no`, `allowusers <user>`. In a second terminal on the Mac: `ssh <user>@<public-ip> true` works, and `ssh -o PubkeyAuthentication=no -o BatchMode=yes <user>@<public-ip> true` fails with `Permission denied (publickey)`.

### 3.4 Automatic security updates

```bash
sudo apt install -y unattended-upgrades apt-listchanges
sudo dpkg-reconfigure unattended-upgrades       # answer "Yes"
sudo unattended-upgrade --dry-run --debug 2>&1 | tail -n 3
systemctl list-timers apt-daily-upgrade.timer
```

**Why:** Debian's security fixes install themselves every day. Docker comes from Docker's own repository (§3.7), which `unattended-upgrades` leaves alone by default, so Docker only changes when you run `sudo apt full-upgrade` yourself (§8).

**Check:** the dry run ends without errors and the timer shows a next run.

### 3.5 Tailscale

**What:** install Tailscale from its repository and join the tailnet.

```bash
sudo mkdir -p --mode=0755 /usr/share/keyrings
curl -fsSL https://pkgs.tailscale.com/stable/debian/trixie.noarmor.gpg | sudo tee /usr/share/keyrings/tailscale-archive-keyring.gpg > /dev/null
curl -fsSL https://pkgs.tailscale.com/stable/debian/trixie.tailscale-keyring.list | sudo tee /etc/apt/sources.list.d/tailscale.list
sudo apt-get update && sudo apt-get install -y tailscale
sudo tailscale up
```

`tailscale up` prints a login link; open it on the Mac and approve the machine. Then, in the admin console on the **Machines** page, open the `…` menu of `jobtrail` and select **Disable key expiry**.

**Why:** a device's key expires after 180 days by default, and a server with an expired key drops off the tailnet — once public SSH is closed (§3.6), that would leave only the provider's web console.

On the Mac, add an SSH alias to `~/.ssh/config`:

```
Host jobtrail
    HostName jobtrail
    User <user>
```

**Check:** `tailscale ip -4` on the server prints a `100.x.y.z` address; on the Mac `ssh jobtrail hostname` prints `jobtrail` (MagicDNS resolves the name, the connection runs over the tailnet).

### 3.6 Firewall: nothing in from the internet

**What:** allow everything that arrives over Tailscale, keep public SSH open only until SSH over Tailscale is proven, then close it.

```bash
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow in on tailscale0
sudo ufw allow 22/tcp                 # public SSH, for the moment
sudo ufw enable
sudo ufw status verbose
```

In a **new** terminal on the Mac, connect over the tailnet: `ssh jobtrail`. Only when that works, close public SSH:

```bash
sudo ufw delete allow 22/tcp
sudo ufw status verbose
```

**Why:** the rule on `tailscale0` comes before `enable`, and public SSH closes last, so no step can lock you out. `/etc/default/ufw` has `IPV6=yes` by default, so the same rules cover IPv6 (`ufw status` shows `(v6)` lines).

**Check:** `ufw status verbose` shows `Default: deny (incoming)` and only `Anywhere on tailscale0` rules. From the Mac, the public address answers on no port (each line ends in a timeout), while `ssh jobtrail true` still works:

```bash
for port in 22 80 443 5432 8080; do nc -vz -G 5 <public-ip> "$port"; done
```

### 3.7 Docker Engine

**What:** Docker from Docker's own repository, with log rotation, and your account in the `docker` group.

```bash
sudo apt update
sudo apt install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: $(. /etc/os-release && echo "$VERSION_CODENAME")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo tee /etc/docker/daemon.json > /dev/null <<'EOF'
{
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "3" }
}
EOF
sudo systemctl restart docker
sudo usermod -aG docker <user>
```

Log out and in again (`exit`, then `ssh jobtrail`) so the group applies.

**Why:** Debian's own `docker.io` package lags behind and has no current Compose plugin. The default log driver never deletes logs; with rotation each container keeps at most three files of 10 MB. The setting only applies to containers created after it, which is why it comes before the first deploy. Membership in `docker` is effectively root access ("The `docker` group grants root-level privileges to the user", Docker docs) — acceptable on a server with a single user, and it lets `make deploy` work without `sudo`.

**Check:** `docker run --rm hello-world` prints "Hello from Docker!"; `docker info --format '{{.LoggingDriver}}'` prints `json-file`; `docker compose version` prints v2.

### 3.8 HTTPS with `tailscale serve`

```bash
sudo tailscale serve --bg 8080
tailscale serve status
```

**What and why:** Tailscale gets a certificate for `jobtrail.<tailnet>` and forwards HTTPS on port 443 of the tailnet address to `http://127.0.0.1:8080`, where nginx will listen. `--bg` stores the setting, so it comes back after a reboot. It adds `X-Forwarded-Proto: https`, which Laravel trusts from private addresses (`bootstrap/app.php`).

**Check:** `tailscale serve status` shows `https://jobtrail.<tailnet> (tailnet only)` with `proxy http://127.0.0.1:8080`. Note the address for §3.9. Until the first deploy, opening it returns an error from Tailscale, because nothing listens on port 8080 yet.

### 3.9 Application directory and secrets

**What:** the directory for the stack, the database password, and Laravel's settings. On the Mac, copy the template:

```bash
scp .env.production.example jobtrail:/tmp/app.env
```

On the server:

```bash
sudo install -d -o <user> -g <user> -m 700 /opt/jobtrail
install -d -m 700 /opt/jobtrail/secrets
cd /opt/jobtrail
mv /tmp/app.env app.env
openssl rand -hex 32 > secrets/db_password
sed -i -e "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" \
       -e "s|^APP_URL=.*|APP_URL=https://jobtrail.<tailnet>|" \
       -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(cat secrets/db_password)|" app.env
chmod 644 app.env secrets/db_password
printf 'COMPOSE_FILE=compose.prod.yaml\n' > .env
```

Copy `app.env` (it holds the database password too) into the password manager: `cat app.env`.

**Why:** `APP_KEY` encrypts sessions and cookies: 32 random bytes, base64-encoded. The database password exists twice, in `secrets/db_password` for PostgreSQL and in `app.env` for Laravel, which cannot read a password from a file. The files are `644` inside a `700` directory: nobody but you and root can reach them on the server, and the containers can read them.

**Check:** `ls -la /opt/jobtrail /opt/jobtrail/secrets` shows `drwx------` for both directories; `grep -cE '^(APP_KEY=base64:.+|APP_URL=https://.+|DB_PASSWORD=.{64})$' app.env` prints `3`.
