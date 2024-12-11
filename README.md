<a name="readme-top"></a>
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]

<br />
<div align="center">
    <h2 align="center">User Management API</h2>
    <p align="center">
        This is a Symfony-based API application for user and company management with role-based access control. The application is built using API Platform v3 and utilizes PostgreSQL as the database.
        <br />
        <a href="https://github.com/ariantron/CompanyUsersAPI"><strong>Explore the docs »</strong></a>
        <br />
        <br />
        <a href="https://github.com/ariantron/CompanyUsersAPI">View Demo</a>
        ·
        <a href="https://github.com/ariantron/CompanyUsersAPI/issues">Report Bug</a>
        ·
        <a href="https://github.com/ariantron/CompanyUsersAPI/issues">Request Feature</a>
    </p>
</div>

## Features

- Role-based access control with three user roles:
    - `ROLE_USER`: Default user role
    - `ROLE_COMPANY_ADMIN`: Manages users within their company
    - `ROLE_SUPER_ADMIN`: Manages all users with elevated privileges

- Two main entities: User and Company
- Comprehensive API endpoints with role-based access
- Strict validation constraints

## Technical Stack

- ![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
- ![Postgres](https://img.shields.io/badge/postgres-%23316192.svg?style=for-the-badge&logo=postgresql&logoColor=white)

## Entity Specifications

### User Entity

- `id`: Integer (auto-generated, immutable)
- `name`:
    - Required
    - String (3-100 characters)
    - Must contain letters and spaces
    - Requires at least one uppercase letter
- `role`:
    - Required
    - Choices: ROLE_USER, ROLE_COMPANY_ADMIN, ROLE_SUPER_ADMIN
    - Role restrictions apply
- `company`:
    - Required for USER and COMPANY_ADMIN roles
    - Relationship to Company entity
    - Not allowed for SUPER_ADMIN

### Company Entity

- `id`: Integer (auto-generated)
- `name`:
    - Required
    - String (5-100 characters)
    - Unique in the database
  
## API Endpoints

### User Endpoints

- `GET /users`
    - **Accessible by**: `ROLE_COMPANY_ADMIN`, `ROLE_SUPER_ADMIN`
    - **Description**: Fetches a paginated list of users.
    - **Visibility Restrictions**:
        - `ROLE_COMPANY_ADMIN` sees users within their company.
        - `ROLE_SUPER_ADMIN` sees all users.

- `GET /users/{id}`
    - **Accessible by**: All roles.
    - **Description**: Fetches details of a specific user by ID.
    - **Visibility Restrictions**:
        - `ROLE_USER` can only access their own data.
        - `ROLE_COMPANY_ADMIN` can access users within their company.
        - `ROLE_SUPER_ADMIN` can access all users.

- `GET /user`
    - **Accessible by**: All roles.
    - **Description**: Fetches the currently authenticated user's details.

- `POST /users`
    - **Accessible by**: `ROLE_COMPANY_ADMIN`, `ROLE_SUPER_ADMIN`
    - **Description**: Creates a new user.
    - **Restrictions**:
        - `ROLE_COMPANY_ADMIN` can only create users within their company.

- `PUT /users/{id}`
    - **Accessible by**: `ROLE_COMPANY_ADMIN`, `ROLE_SUPER_ADMIN`
    - **Description**: Updates details of an existing user.
    - **Restrictions**:
        - `ROLE_COMPANY_ADMIN` can only update users within their company.

- `PUT /users/{id}/set-company/{companyId}`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Assigns a user to a specific company.

- `PUT /users/{id}/unset-company`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Removes a user from their assigned company.

- `DELETE /users/{id}`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Deletes a user by ID.

### Company Endpoints

- `GET /companies`
    - **Accessible by**: All roles.
    - **Description**: Fetches a paginated list of companies.

- `GET /companies/{id}`
    - **Accessible by**: All roles.
    - **Description**: Fetches details of a specific company by ID.

- `GET /companies/{id}/users`
    - **Accessible by**: `ROLE_COMPANY_ADMIN`, `ROLE_SUPER_ADMIN`
    - **Description**: Fetches a list of users belonging to a specific company.
    - **Restrictions**:
        - `ROLE_COMPANY_ADMIN` can only fetch users from their own company.

- `POST /companies`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Creates a new company.

- `PUT /companies/{id}`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Updates details of a specific company.

- `DELETE /companies/{id}`
    - **Accessible by**: `ROLE_SUPER_ADMIN`
    - **Description**: Deletes a company by ID.

## Development Setup

### Prerequisites

- PHP 8.1+
- Symfony CLI
- Composer
- PostgresSQL

### Installation Steps

1. Clone the repository
   ```bash
   git clone https://github.com/ariantron/CompanyUsersAPI
   cd CompanyUsersAPI
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Copy environment files
   ```bash
   cp .env.example .env
   cp .env.test.example .env.test
   ```

4. Configure database connection in `.env`
   ```env
   DATABASE_URL="postgresql://username:password@localhost:5432/dbname?serverVersion=15&charset=utf8"
   ```

5. Create database and run migrations
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Generate Fake Data
   ```bash
   php bin/console app:gen-fake
   ```

### Running the Application

```bash
symfony server:start
```

## Testing

### Setup Test Environment

1. Create the test database:
   ```bash
   php bin/console doctrine:database:create --env=test
   ```

2. Create the schema for the test database:
   ```bash
   php bin/console doctrine:schema:create --env=test
   ```

### Running Tests

```bash
php bin/phpunit
```

### Test Coverage

- Endpoint access permissions
- Validation constraints
- Role-based restrictions
- Entity relationships

## Security Considerations

- Role-based access control
- Validation of user inputs
- Prevention of unauthorized actions

## Impersonation

- SUPER_ADMIN can impersonate any user
- Implemented with careful security checks

## License

Distributed under the MIT License. See LICENSE.txt for more information.

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/ariantron/CompanyUsersAPI.svg?style=for-the-badge
[contributors-url]: https://github.com/ariantron/CompanyUsersAPI/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/ariantron/CompanyUsersAPI.svg?style=for-the-badge
[forks-url]: https://github.com/ariantron/CompanyUsersAPI/network/members
[stars-shield]: https://img.shields.io/github/stars/ariantron/CompanyUsersAPI.svg?style=for-the-badge
[stars-url]: https://github.com/ariantron/CompanyUsersAPI/stargazers
[issues-shield]: https://img.shields.io/github/issues/ariantron/CompanyUsersAPI.svg?style=for-the-badge
[issues-url]: https://github.com/ariantron/CompanyUsersAPI/issues
[license-shield]: https://img.shields.io/github/license/ariantron/CompanyUsersAPI.svg?style=for-the-badge
[license-url]: https://github.com/ariantron/CompanyUsersAPI/blob/master/LICENSE.txt
