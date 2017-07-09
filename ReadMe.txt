PHP Website registration form.

1. Registers user, send the user a welcome email, and loads a welcome page.
2. Uses prepared statments to prevent sql injection.
3. Email and username check. (In case previously registered)
4. ReCaptcha
5. Provides error message incase previously registered user attempts to register again while logged in. Must set cookies upon login to detect this. Adjust the check as needed.

Database info:
users_table with username, email, first name, last name, password, gender, country, avatar, ip, signup, & lastlogin.

