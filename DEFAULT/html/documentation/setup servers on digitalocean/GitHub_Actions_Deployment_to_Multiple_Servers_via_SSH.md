# GitHub Actions Deployment to Multiple Servers via SSH

First of all you have to access your server and then use this command 
```bash
ssh-keygen -t rsa -b 4096 -C "your-github-email@mail.com"
```
it will start generating a file called id_rsa now you can name this file what ever you want you can save it in any directory you want then hit Enter then if you want to add passphrase enter it if not just press enter to keep it passwordless  

Note: you can press Enter to keep it in the same directory suggested by your system
---

## Secondly 

- you have to copy your generated file to /auhorized_keys using this command
```bash
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
```

---

## thirdly

1. use this command to create a config file 
```bash
nano nano ~/.ssh/config
```
inside of it you have to paste this config 

```config
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_rsa
  IdentitiesOnly yes

```

---

## 4.step
now you have to show the public key
```bash
cat ~/.ssh/id_rsa.pub
```
 you will have to enter it in 
your github profile>setting>SSH and GPG keys> New SSH key 
now enter the name of the key and paste the public key inside of it 

### 5.step 

now you will show the private key 
```bash
cat ~/.ssh/id_rsa
```
you will have to enter it inside your repository in 
settings>Secret and variables>Actions 
create new repository secret and paste your private key inside of it and memorise the name we will use it later in the our github config file inside our project

### 6.step 

to make sure you are connected to your github account use this command
```bash
ssh -T git@github.com
```
you will see this message 

Hi your-user-name You've successfully authenticated, but GitHub does not provide shell access.

### 7.step

now you have to setup the config file inside your project 
create a folder inside your root folder and name it .github/workflows/deploy.yml

### 8.step 

now you will add this config inside your deploy.yml file 

```yml
name: Deploy to DigitalOcean Droplets

on:
  push:
    branches:
      - main  # Or your specific branch

jobs:
  deploy:
    runs-on: self-hosted

    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      # Install SSH Key for First Server
      - name: Install SSH Key
        uses: webfactory/ssh-agent@v0.5.4
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}

      # Mark /var/www/html as a safe directory for First Server
      - name: Mark /var/www/html as a safe directory
        run: |
          ssh -o StrictHostKeyChecking=no username@Server_ip "
            git config --global --add safe.directory /var/www/html
          "

      # Deploy to First Server
      - name: Deploy to First Server
        run: |
          ssh -o StrictHostKeyChecking=no username@Server_ip "
            cd /var/www/html &&
            git pull origin main &&
            php artisan cache:clear &&
            php artisan config:clear &&
            php artisan route:clear &&
            php artisan view:clear &&
            sudo systemctl restart apache2
          "

      # Install SSH Key for Second Server
      - name: Install SSH Key for Second Server
        uses: webfactory/ssh-agent@v0.5.4
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY_SECOND_SERVER }}

      # Deploy to Second Server
      - name: Deploy to Second Server
        run: |
          ssh -o StrictHostKeyChecking=no username@Server_ip "
            cd /var/www/html &&
            git pull origin main &&
            php artisan cache:clear &&
            php artisan config:clear &&
            php artisan route:clear &&
            php artisan view:clear &&
            sudo systemctl restart apache2
          "
```
in this file i have setup github actions for 2 servers you will have to use your server username and ip adress and for the private key you entered earlier in yout github actions secret and variable you have to add the variable like this 

```yml
    ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY_SECOND_SERVER }}
```

now test your Actions to see if it works
