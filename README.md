# CrispCMS Docker

With Docker you can easily deploy Crisp

The Docker Image requires a running Phoenix instance with an accessible Postgres Database before you can deploy it


Pull the Image
```
docker pull jbackpcat/tosdr-crisp
```

The Docker Image exposes port 80, 3306 and 6379. SSL is currently not supported, only via reverse proxies.



The following environment variables are used:



**MYSQL_HOSTNAME=localhost** - Your MySQL Server hostname, by default uses internal server **OPTIONAL**

**MYSQL_USERNAME=tosdr** - The username of your mysql server, this affects the default created user of the internal server **OPTIONAL**

**MYSQL_DATABASE=tosdr** - The name of the database to use, this affects the default created database of the internal server **OPTIONAL**

**REDIS_HOST=localhost** - Your Redis Server hostname, by default uses internal server **OPTIONAL**

**REDIS_PORT=6379** - Your redis port **OPTIONAL**

**REDIS_AUTH=tosdr** - The password of your redis server, will create one for the internal server if set **OPTIONAL**
 
**POSTGRES_URI=(empty)** - The URI of your Postgres instance where Phoenix is hosted, **THIS IS REQUIRED** *postgres://user:secret@localhost:5432/mydatabasename*

**CDN_URL=(empty)** - Optional URL to you PUSH CDN *https://mycdn.example.com*



```
docker run -e "POSTGRES_URI=postgres://user:secret@localhost:5432/mydatabasename" -p 80:80 jbackpcat/tosdr-crisp
```
