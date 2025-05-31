# The LinkBridge Project

**LinkBridge** is a service that lets you send a link from your device to any other device with a browser.

## How does it work?

1. The receiver opens the [LinkBridge](https://LinkBridge.ru) website and shares a code with the sender.
2. The sender enters the receiver’s code and sends the link.
3. The receiver’s device opens the link sent by the sender.

> This is incredibly convenient if you are using a set-top box or Smart TV!

---

## 🌍 Localization

LinkBridge supports:

- English
- Русский
- Беларуская
- Қазақша
- Deutsch
- Italiano
- Հայերեն

---

## ⚙️ Installation

The application requires PHP >= 8.2.
Composer v2 is needed to install the application dependencies.

---

Prepare workdir

```bash
mkdir -p /var/www/linkbridge_app ;
cd /var/www/linkbridge_app ;
```

Download LinkBridge App

```bash
git clone https://github.com/LinkBridge-ru/LinkBridge-web.git . ;
```

Install dependencies

```bash
composer install
```

---

## QR Code

If you have your own QR code generation service, set it in the `.env` file. Example:

```dotenv
THIS_PROJECT_QR_VENDOR="https://api.qrserver.com/v1/create-qr-code/?margin=20&size=300x300&data="
```

---

## Reverse Proxy and HTTPS

If you are using a reverse proxy server,
make sure to specify its correct address in the `TRUSTED_PROXIES` entry in `.env`!

Default local ranges example:

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

> If you're unsure what you are doing, add the IP address of your reverse proxy server after a comma!
> This is necessary for Symfony’s security system to correctly detect the `https` protocol.

---

## Database

By default, LinkBridge is configured to store temporary data in SQLite.

> [!NOTE]
> If you need to use your own database server, check the documentation
> [Doctrine/ORM](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url)
>
> Ignore this notice if it does not apply to you.

> [!IMPORTANT]
> Create and apply the migrations **ONCE** when you set up the project!

Create the database migration file:

```bash
php bin/console make:migration
```

Apply the DataBase migration:

```bash
php bin/console doctrine:migrations:migrate
```

Now you can use the service!

When you finish testing, switch the application into production mode in the `.env` file.

```dotenv
APP_ENV=prod
```

---

## Permissions error

If you encounter permission errors, run this in the app directory:

```bash
cd /var/www/linkbridge_app ;

find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chown -R 33:33 * ; # mark `www-data` user as owner.
```

Then clear the cache:

```bash
php bin/console cache:clear ;
```
