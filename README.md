Jesuit Science Network
======================

This is the source code of the JSN application that was developed during Dagmar Mrozik's PhD thesis "The Jesuit Science Network: A digital prosopography on Jesuit scholars in the early modern sciences" and was available online from 2018 through 2020 on jesuitscience.net (domain has been grabbed after expiring and is not in our control any more).

The thesis itself can be found online [here](http://elpub.bib.uni-wuppertal.de/servlets/DocumentServlet?id=8981). It contains further information about the projects, its goals and its features.

Architecture
------------

The JSN is built as a web application backed by an SQL database. During her work on the matter, Dagmar collected prosopographical data using a [BBAW](https://www.bbaw.de) research project called "Personendaten-Repositorium", which stores data in XML files.

The JSN used these hosted XML files as basis for further normalization, filtering and processing, storing the results relationally in an SQL database.

The web frontend then serves the data in a user-friendly and approachable manner.


Installing locally
------------------

You can install a local copy of this software to browse the database yourself. 

**NOTE:** This project is old and hasn't been updated in a while. Please check dependencies and update as necessary when planning to publicly run this software on the internet.

You need the following requirements installed and ready: 

 * PHP 7
 * [Composer](https://getcomposer.org/download/)
 * [Symfony CLI](https://symfony.com/download)
 * MySQL or MariaDB database server (native or dockerized)
 * If you want to change things around, you'll also need Node.js

For the rest of the instructions you'll also need a basic understanding of PHP web applications and the use of the terminal.

### Instructions

1. Download [all release files](https://github.com/jesuitsciencenetwork/web/releases/tag/1.0.0) to a folder of your choosing. 

2. Connect to your MySQL/MariaDB server. Import the supplied SQL file `jsn.sql.gz` into your database using a db client of your choice. Be sure to change the database name if needed (`jsn` by default). Create a user with access to the database.

3. Unzip jsn.zip. It should extract a folder `jsn` with all the source code files.

4. Change into the `jsn` directory, then run the following commands to install dependencies:
       
       composer install
       yarn install

    You will be asked to provide your MySQL connection parameters during installation. Provide the database and user created in step 1.

5. Build frontend assets:
  
       ./node_modules/.bin/encore production

5. Start the application:

        symfony server:start --dir=html

6. You can now access the JSN in your browser at http://localhost:8000/


IDI Cache
---------

Since the aforementioned BBAW PDR project is not available anymore, you cannot use it to recreate the SQL data from scratch. This is why a full SQL dump is provided with the project.

If you want to alter anything about the data processing, you need to re-import the SQL from XML sources (so called IDI files). To do so, we provide a local cache of the last version of IDI data for this project.

To use it, unzip the contents of `idi.zip` to `var/cache/idi/` (creating the directory structure if necessary).
   
Empty the database and recreate its structure:
      
    php bin/console database:drop --force
    php bin/console database:create
    php bin/console doctrine:schema:create --force --dump-sql

Finally, run the command
      
          php bin/console jsn:import
          
to run the import and recreate the database contents. This should fetch IDIs from your local cache instead of the now-defunct PDR host.
