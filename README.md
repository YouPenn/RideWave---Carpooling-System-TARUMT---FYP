# RideWave---Carpooling-System-TARUMT---FYP
Carpooling System for TARUMT 

Steps to Launch RideWave:Carpooling System

1. Install Required Software:
XAMPP: Download and install XAMPP Version 8.2.12, which provides a local web server environment for PHP and MySQL.
Apache NetBeans IDE 12.0: Download and install Apache NetBeans IDE 12.0, an integrated development environment (IDE) for working with PHP and other programming languages.

2. Create the Database:
Use the XAMPP Control Panel to start the Apache and MySQL services.
Access phpMyAdmin by navigating to http://localhost/phpmyadmin in your web browser.
Create a new database call “carpooldb” for the project.
Import the provided SQL file into this database to set up the necessary tables and data.

3. Unzip the Project Files:
Extract the project files to the htdocs directory within the XAMPP installation folder (usually found at C:\xampp\htdocs\).
The full path should look something like "C:\xampp\htdocs\RideWave" and "C:\xampp\htdocs\RideWave_Admin".

4. Open the Project in NetBeans:
Launch Apache NetBeans IDE 12.0.
Open the project by navigating to "File" then "Open Project" and selecting the extracted "RideWave" and "RideWave_Admin" folder.

5. Run the Project:
In NetBeans, run the project.
This action should open the user homepage of "RideWave" in your default web browser.

6. User Login:
To log in as a user, use the following credentials:
User Email: youpenn2003@gmail.com
or Student ID: 23JMR08036
User Password: Abc@123456

Alternatively, you can register a new account using your own email address and Student ID.


**Not include Admin side yet, I will update in future

7. Admin Login:
To log in as an admin, use the following credentials:
Email	: sooyk-jm21@student.tarc.edu.my
Password: RootAdmin!

*Admin login requires OTP, you need to open XAMPP control panel, click the "Start" button for "Apache" and "MySQL", then click the "Admin" button for "MySQL" to enter phpMyAdmin. Click the "carpooldb" and "admin" tables to change the current email to your email.


