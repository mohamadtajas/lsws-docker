
# Setting Up a Self-Hosted GitHub Actions Runner

This guide walks you through the process of setting up a **self-hosted GitHub Actions runner**. Self-hosted runners allow you to run GitHub Actions workflows on your own machines, providing greater control over the hardware, software, and network configuration.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Step 1: Add a New Runner in GitHub](#step-1-add-a-new-runner-in-github)
- [Step 2: Set Up the Runner on Your Machine](#step-2-set-up-the-runner-on-your-machine)
  - [1. Create a Directory for the Runner](#1-create-a-directory-for-the-runner)
  - [2. Download the Latest Runner Package](#2-download-the-latest-runner-package)
  - [3. (Optional) Validate the Package Hash](#3-optional-validate-the-package-hash)
  - [4. Extract the Runner Installer](#4-extract-the-runner-installer)
  - [5. Configure the Runner](#5-configure-the-runner)
  - [6. Run the Runner](#6-run-the-runner)
- [Step 3: Use the Self-Hosted Runner in Your Workflows](#step-3-use-the-self-hosted-runner-in-your-workflows)
  - [Example Workflow File](#example-workflow-file)
- [Additional Resources](#additional-resources)
- [Security Considerations](#security-considerations)

---

## Prerequisites

Before you begin, ensure you have the following:

- A GitHub account with access to the repository where you want to add the runner.
- Administrative access to the machine where you intend to run the self-hosted runner.
- [cURL](https://curl.se/) installed on your machine.
- Basic knowledge of using the command line.

## Step 1: Add a New Runner in GitHub

1. **Navigate to Your GitHub Account Settings:**
   - Log in to your GitHub account.
   - Click on your profile picture in the top-right corner and select **Settings**.

2. **Access the Actions Settings:**
   - In the left sidebar, click on **Actions**.

3. **Manage Runners:**
   - Under **Actions**, select **Runners**.
   - Click on **Add runner**.

4. **Choose Your Operating System:**
   - Select the operating system (OS) of the machine where the runner will be hosted (e.g., Linux, macOS, Windows).

5. **Obtain the Registration Token:**
   - GitHub will provide you with a unique token and a set of instructions. Keep this page open as you will need the token in the next steps.

## Step 2: Set Up the Runner on Your Machine

Follow these steps on the machine where you want to host the runner.

### 1. Create a Directory for the Runner

Create a dedicated directory for the runner and navigate into it:

```bash
mkdir actions-runner && cd actions-runner
```

### 2. Download the Latest Runner Package

Download the runner package corresponding to your OS. Replace the URL with the latest version if necessary.

```bash
curl -o actions-runner-linux-x64-2.320.0.tar.gz -L https://github.com/actions/runner/releases/download/v2.320.0/actions-runner-linux-x64-2.320.0.tar.gz
```

### 3. (Optional) Validate the Package Hash

It's good practice to verify the integrity of the downloaded package. Compare the SHA-256 hash provided by GitHub with the one you compute.

```bash
echo "93ac1b7ce743ee85b5d386f5c1787385ef07b3d7c728ff66ce0d3813d5f46900  actions-runner-linux-x64-2.320.0.tar.gz" | shasum -a 256 -c
```

You should see a message indicating that the hash is correct:

```
actions-runner-linux-x64-2.320.0.tar.gz: OK
```

### 4. Extract the Runner Installer

Unpack the downloaded tarball:

```bash
tar xzf ./actions-runner-linux-x64-2.320.0.tar.gz
```

### 5. Configure the Runner

Use the configuration script to set up the runner. Replace the `<repository-url>` and `<token>` with your repository URL and the token obtained from GitHub.

```bash
./config.sh --url https://github.com/Ab00dSte/stp --token BLYKJBWGDPQZEIE5WSNB5H3HENJKM
```

**Note:** Keep your token secure. Do not share it or commit it to your repository.

### 6. Run the Runner

Start the runner process:

```bash
./run.sh
```

The runner should now be active and connected to your GitHub repository.

## Step 3: Use the Self-Hosted Runner in Your Workflows

To utilize your self-hosted runner in GitHub Actions workflows, specify `self-hosted` in the `runs-on` attribute of your workflow YAML file. This directs the job to run on your custom runner.

```yaml
jobs:
  build:
    runs-on: self-hosted
    steps:
      - name: Checkout repository
        uses: actions/checkout@v2
      # Add additional steps here
```

### Example Workflow File

```yaml
name: CI

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  build:
    runs-on: self-hosted
    steps:
      - name: Checkout repository
        uses: actions/checkout@v2

      - name: Set up Node.js
        uses: actions/setup-node@v2
        with:
          node-version: '14'

      - name: Install dependencies
        run: npm install

      - name: Run tests
        run: npm test
```

## Additional Resources

- [GitHub Docs: Adding self-hosted runners](https://docs.github.com/en/actions/hosting-your-own-runners/adding-self-hosted-runners)
- [GitHub Actions Runner Repository](https://github.com/actions/runner)
- [Managing Runner Applications](https://docs.github.com/en/actions/hosting-your-own-runners/managing-self-hosted-runners)

---

## Security Considerations

- **Keep Your Runner Updated:** Regularly check for updates to the GitHub Actions runner to ensure you have the latest security patches and features.
- **Secure Your Machine:** Since the runner has access to your repository, ensure that the machine is secure and only authorized personnel have access.
- **Monitor Runner Activity:** Keep an eye on the runner's activity to detect any unauthorized or suspicious operations.

By following this guide, you can successfully set up and utilize a self-hosted GitHub Actions runner, giving you more control over your CI/CD workflows.
