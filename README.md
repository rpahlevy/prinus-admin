# PRINUS ADMIN

1. Duplicate .env.template to .env
2. Masukkan detail database & APP_URL (localhost:8080 jika menggunakan built in php server)
3. `composer install` && `composer start` (built in php server)

## TODO

1. [x] Tenant : List
2. [x] Tenant : Create & Update
3. [x] Tenant : Details (List all user & logger in tenant)
4. [x] Tenant : Details Action (Unlink & set enable user, unlink & delete logger)
5. [x] Users : List
6. [x] Users : Create, Update, & Disabled
7. [x] Logger : List
8. [x] Logger : Create, Update, & Delete
9. [x] Auth : Login & Logout
10. [x] Auth : Filter Level -> Admin & User Tenant

## Users

Untuk dapat menambah / set password user:

1. Comment AdminMiddleware pada group `/user`
2. Akses path `/user`
3. Tambahkan admin baru / set password admin yg sudah ada (user tanpa tenant)
4. Uncomment AdminMiddleware pada group `/user`
5. Login pada path `/login`