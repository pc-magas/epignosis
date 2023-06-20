# VaccationMS
A simple vaccation management system for growing businesses

> NOTE 1: this is a recruitment test for Epignosis https://www.epignosishq.com

> NOTE 2: If you have run first `docker exec -ti -u www-data epignosis_recruitment_php81 /bin/bash` you cann ommit the docker command ant is params.

# How to run

```
cp ./src/.env.local ./src/.env
docker-compose up -d
docker exec -ti -u www-data epignosis_recruitment_php81 composer install
```

# Database Generation and Seeding

```
docker exec -ti -u www-data epignosis_recruitment_php81 ./vendor/bin/phinx migrate

# At output you'll see the seeded users. Default password are 1234 (used in local development)
docker exec -ti -u www-data epignosis_recruitment_php81 ./vendor/bin/phinx seed:run
```

# Unit tests

```
docker exec -ti -u www-data epignosis_recruitment_php81 ./vendor/bin/phinx migrate
```

# APP URLs

The app is served via http://127.0.0.1:8080
Mailhog and all emails are listed upon http://127.0.0.1:8025 (test SMTP server web panel)
