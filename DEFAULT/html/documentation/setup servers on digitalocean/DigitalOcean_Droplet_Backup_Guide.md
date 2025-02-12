
# DigitalOcean Droplet Full Server Backup Guide

To back up the entire server on DigitalOcean, here’s a step-by-step guide that covers compressing, transferring, and restoring the entire file system.

---

## 1. Create a Full System Backup with Compression

First, log in to your source droplet. Use `tar` to compress and back up the entire filesystem, excluding directories like `proc`, `sys`, `dev`, and `tmp` that are regenerated on reboot:

```bash
sudo tar --exclude=/proc --exclude=/sys --exclude=/dev --exclude=/tmp --exclude=/mnt --exclude=/media --exclude=/lost+found -czvf /backup-server.tar.gz /
```

This command will create a compressed file named `backup-server.tar.gz` in the root directory (`/`). Adjust the path if you want to save it elsewhere.

---

## 2. Transfer the Backup File to the New Droplet

Once the backup is created, transfer it to the new droplet using `scp` or `rsync`. Replace `user` and `new-droplet-ip` with the appropriate values for your new droplet:

```bash
scp /backup-server.tar.gz user@new-droplet-ip:/path/to/destination
```

For large backups, `rsync` is a better option as it’s more resilient to network interruptions:

```bash
rsync -avz /backup-server.tar.gz user@new-droplet-ip:/path/to/destination
```

---

## 3. Prepare the New Droplet

Make sure the new droplet has at least the same software and basic configuration as the source droplet. For example, if you used `Ubuntu` on the source, ensure the target droplet runs `Ubuntu` as well.

---

## 4. Restore the Backup on the New Droplet

On the new droplet, navigate to where the backup file is located and extract it to restore the entire system:

```bash
sudo tar -xzvf /path/to/destination/backup-server.tar.gz -C /
```

**Warning:** This will overwrite existing files, so be careful when restoring to a running system.

---

## 5. Adjust Configuration Files

After restoration, review and update configuration files as needed:
- **Network Settings**: Update IP addresses, hostnames, or any network-related configurations.
- **Application Configuration**: Update any application-specific configurations if the new droplet differs in setup.

---

## 6. Reboot the Droplet

To apply all changes, restart the new droplet:

```bash
sudo reboot
```

---

### Additional Tips

- **Database Backups**: If your database is large, consider backing it up separately using `mysqldump` or `pg_dump` and restoring it after the main system is in place.
- **Automated Backups**: DigitalOcean provides automated backups for droplets, which may be easier if you need frequent or scheduled backups.

---

This process will transfer the entire system, ensuring your new droplet closely matches the original server's configuration.
