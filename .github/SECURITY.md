# Security Policy

## Reporting a Vulnerability

Please **do not report security vulnerabilities through public GitHub issues.**

Instead, use one of these private channels:

- **GitHub:** [Report a vulnerability](https://github.com/artwork-software/artwork/security/advisories/new) via private vulnerability reporting
- **E-Mail:** jannik.mueller@caldero-systems.de

Please include as much of the following as you can:

- A description of the vulnerability and its potential impact
- Steps to reproduce, or a proof of concept
- The affected version or commit, and your installation method (Docker or standalone)

We will confirm receipt of your report, keep you informed about the progress, and credit you in the fix release if you wish.

## Supported Versions

Security fixes are provided for the latest release. If you run an older version, please update to the current release before reporting, as the issue may already be fixed.

## Scope

artwork is self-hosted software. Issues caused purely by misconfiguration of an individual installation (e.g. exposed `.env` files, missing TLS) are outside the scope of this policy — but if artwork's defaults or documentation make such a misconfiguration likely, we do want to hear about it.

## Coordinated Disclosure

We follow coordinated disclosure: once a report is confirmed, we agree on a publication date with the reporter that fits the fix release. Advisories are published as GitHub Security Advisories for this repository; a CVE is requested through GitHub when the reporter or the severity calls for it. We do not run a paid bug bounty. On request, reporters are credited in the Acknowledgements section below and in the advisory; without a request, reports are handled anonymously.

## Acknowledgements

We thank the following people for responsibly reporting security issues in artwork:

| Date | Reporter | Report |
| --- | --- | --- |
| September 2026 | kta1kri | Three missing server-side authorization checks (personnel, tasks and funding sources), reported privately with reproduction steps and handled under coordinated disclosure. |
| September 2026 | archnexus707 | Missing authorization on funding source file actions (download, replace, delete), reported privately via GitHub with reproduction steps and handled under coordinated disclosure. |
