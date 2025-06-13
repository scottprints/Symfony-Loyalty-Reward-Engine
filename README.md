# Loyalty Reward Engine

Welcome to the Loyalty Reward Engine project. This application provides a API for managing customer profiles, points transactions, and prize redemptions.

## API Endpoints

### Customer Management

- **GET /api/customer/profile**
  - **Description**: Retrieve the profile of the authenticated customer.
  - **Postman**: Set the request type to GET, URL to `http://localhost:8000/api/customer/profile`, and add an Authorization header with the value `Bearer <your_jwt_token>`.

- **GET /api/customer/profiles**
  - **Description**: Retrieve profiles of all customers.
  - **Postman**: Set the request type to GET, URL to `http://localhost:8000/api/customer/profiles`, and add an Authorization header with the value `Bearer <your_jwt_token>`.

- **GET /api/customer/transactions**
  - **Description**: Retrieve points transactions for the authenticated customer.
  - **Postman**: Set the request type to GET, URL to `http://localhost:8000/api/customer/transactions`, and add an Authorization header with the value `Bearer <your_jwt_token>`.

### Prize Management

- **POST /api/spin**
  - **Description**: Spin the wheel to win prizes (requires authentication).
  - **Postman**: Set the request type to POST, URL to `http://localhost:8000/api/spin`, and add an Authorization header with the value `Bearer <your_jwt_token>`.

- **GET /api/prizes**
  - **Description**: List all active prizes.
  - **Postman**: Set the request type to GET and URL to `http://localhost:8000/api/prizes`.

- **POST /api/redeem/{id}**
  - **Description**: Redeem a prize by its ID (requires authentication).
  - **Postman**: Set the request type to POST, URL to `http://localhost:8000/api/redeem/<prize_id>`, and add an Authorization header with the value `Bearer <your_jwt_token>`.

### Authentication

- **POST /api/login_check**
  - **Description**: Authenticate a user and receive a JWT token.
  - **Postman**: Set the request type to POST, URL to `http://localhost:8000/api/login_check`, set the body to raw JSON with the content `{"username": "user@example.com", "password": "password123"}`, and set the Content-Type header to `application/json`.

- **POST /api/register**
  - **Description**: Register a new user (if implemented).
  - **Postman**: Set the request type to POST, URL to `http://localhost:8000/api/register`, set the body to raw JSON with the content `{"email": "newuser@example.com", "password": "newpassword123"}`, and set the Content-Type header to `application/json`. 