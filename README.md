# Bangladesh Dev Platform - API Service

This repository houses the core API services for the Bangladesh Dev Platform. It provides the backend infrastructure for various micro-applications, offering authentication, user management, and other essential functionalities.

## Purpose

The primary goal of this repository is to centralize and provide robust, scalable, and secure API endpoints for the broader Bangladesh Dev Platform ecosystem. It aims to offer a consistent interface for client applications while allowing flexibility in backend technology choices.

## Technology Overview

To support diverse development needs and facilitate experimentation, this repository currently hosts two distinct API implementations:

- **Node.js API (`api-node-dev` branch):** A modern, JavaScript/TypeScript-based API service.
- **PHP API (`api-php-dev` branch):** A robust, PHP-based API service.

Both implementations aim to provide similar core functionalities, allowing for comparison, migration, or use in different contexts.

## Repository Structure

-   `main`: This branch is dedicated to high-level documentation, project overview, and general repository information. It *does not* contain any application code.
-   `api-node-dev/`: Contains the full source code and related assets for the Node.js API implementation.
-   `api-php-dev/`: Contains the full source code and related assets for the PHP API implementation.

## Getting Started

To explore or develop with either API:

1.  **Clone this repository:**
    ```bash
    git clone git@github.com:bangladesh-dev-platform/service-api.git
    cd service-api
    ```
2.  **Switch to your desired API branch:**
    *   For the Node.js API: `git checkout api-node-dev`
    *   For the PHP API: `git checkout api-php-dev`
3.  Refer to the `README.md` file *within that specific branch* for detailed setup, installation, configuration, and usage instructions.

## Contributing

We welcome contributions! Please refer to the specific branch's `README.md` and any `CONTRIBUTING.md` files (if present) for guidelines on how to contribute to that particular API implementation.