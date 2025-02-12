
# DigitalOcean Spaces Setup for Shared Storage with s3fs

This document outlines the steps to set up DigitalOcean Spaces as shared storage across two droplets using `s3fs` and mount it for shared file access. This setup will ensure the shared storage is accessible even after a droplet restart.

## Prerequisites

- DigitalOcean Spaces created in the appropriate data center (e.g., `fra1` for Frankfurt).
- Two droplets behind a load balancer.

## Step 1: Create API Key for Spaces Access

1. Log in to your DigitalOcean account.
2. Go to the **API** section and create a new **Personal Access Token** with read/write permissions for Spaces.
3. Make a note of your **SPACE_API_ACCESS_KEY** and **SPACE_API_SECRET_KEY**.

## Step 2: Install `s3fs` on Each Droplet

Run the following commands to update the package list and install `s3fs`:

```bash
sudo apt update
sudo apt install s3fs -y
```

## Step 3: Configure Credentials

Create a password file to store your API keys and set the appropriate permissions:

```bash
echo "SPACE_API_ACCESS_KEY:SPACE_API_SECRET_KEY" > ~/.passwd-s3fs

echo "DO00Z6HLK6KWQBKH49VU:SGpkbz74fZZ7TfHV/FQz2Aw4g63zo2dXz80lcs/jw20" > ~/.passwd-s3fs

chmod 600 ~/.passwd-s3fs
```

**Replace `SPACE_API_ACCESS_KEY` and `SPACE_API_SECRET_KEY` with your actual keys.**

## Step 4: Create a Mount Point

Make a directory where the shared space will be mounted:

```bash
mkdir -p /mnt/shared-space
```

## Step 5: Mount DigitalOcean Space with `s3fs`

To mount the space, use the following command:

```bash
sudo s3fs stp-shared-files /mnt/shared-space \
-o url=https://fra1.digitaloceanspaces.com \
-o passwd_file=/root/.passwd-s3fs \
-o allow_other \
-o use_cache=/tmp/s3fs_cache
```

**Replace `shared-files-prandly` with your Space name.**

## Step 6: Create a Symbolic Link

Link the mounted directory to the web server’s root:

```bash
ln -s /mnt/shared-space/YOUR_FOLDER /var/www/html/
```

**Replace `YOUR_FOLDER` with the specific folder name in your DigitalOcean Space.**

## Step 7: Ensure Persistence on Restart

To remount automatically after each restart, add the `s3fs` mount command to your startup script or use cron jobs.

Example using `@reboot` in crontab:

```bash
sudo crontab -e
```

Add the following line to the file:

```bash
@reboot s3fs shared-files-prandly /mnt/shared-space -o url=https://fra1.digitaloceanspaces.com -o passwd_file=/root/.passwd-s3fs -o allow_other
```

## Notes

- Ensure `s3fs` permissions and mounting settings are configured to allow proper access for both droplets.
- Test mounting and file access across droplets to confirm that shared files work seamlessly with your load balancer setup.

---

This setup enables shared storage with DigitalOcean Spaces, providing scalable and reliable file storage across multiple droplets.
