# Jeopardy-Home-Game

A simple web application for generating random Jeopardy games that you can play with friends!

This requires a Symfony backend (index.php) and PGSQL DB to store the clue metadata in.

High level steps:

1. Modify the scraper to point to your DB.
2. Run the scraper and confirm the data has been parsed successfully.
3. Modify the backend to point to your DB.
4. Start your PHP server
5. Visit localhost::8080/jeopardy
