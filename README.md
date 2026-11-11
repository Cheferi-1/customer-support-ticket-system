# LAB 06 - Customer Support Ticket

## Description

This project is a customer support ticket system developed
using HTML, CSS, PHP and MySQL.

Customers can submit complaints and support requests.
They receive a ticket reference after successful submission.

## Technologies

- HTML
- CSS
- PHP
- MySQL
- PDO

## Database Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin.
3. Select the SQL tab.
4. Execute database.sql.
5. The database lab06_support will be created.

## Project Setup

1. Copy the folder into C:\xampp\htdocs.
2. Start Apache and MySQL.
3. Open your browser.
4. Visit:

http://localhost/LAB06_Support_Ticket/

## Validation Rules

- Customer name is required.
- Email must be valid.
- Issue category is required.
- Priority is required.
- Subject must contain 5 to 120 characters.
- Description must contain 20 to 2000 characters.
- Screenshot is optional.
- Only PNG and JPEG screenshots are accepted.
- Screenshot must be smaller than 2MB.
- Confirmation is required.

## Database

Database: lab06_support

Table: support_tickets

New tickets receive Pending status automatically.

## Security

- PDO is used for database connections.
- Prepared statements are used for insertion.
- Uploaded images are checked on the server.
- Generated filenames are used for uploaded images.
- User input is validated.
- Output is escaped using htmlspecialchars().

## Extension

The system displays submitted support tickets
with their current status.