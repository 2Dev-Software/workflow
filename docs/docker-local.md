# Docker Local Workflow

ชุดนี้ทำไว้เพื่อให้เปิดโปรเจกต์บน environment เดียวกันด้วย Docker ทั้ง PHP และ MariaDB

## สิ่งที่ได้

- PHP + Apache สำหรับรันเว็บ
- MariaDB พร้อม init จาก dump ปัจจุบันของระบบ
- app container รอ DB แล้วรัน migrations อัตโนมัติ

## เริ่มใช้งาน

1. คัดลอกไฟล์ environment ของ Docker

```bash
cp .env.docker.example .env.docker
```

2. ถ้าต้องการอัปเดต dump จากฐานข้อมูลปัจจุบันบนเครื่องนี้

```bash
bash scripts/docker/export-current-db.sh
```

สคริปต์นี้จะ export ฐานข้อมูลจากเครื่อง local ปัจจุบันของคุณ และล้างไฟล์ `.sql` เก่าใน `docker/mysql/initdb` ให้เหลือ base dump ตัวเดียว

3. ถ้าต้องการให้ environment ตรงกับเครื่องต้นทางจริง รวมไฟล์แนบและรูปโปรไฟล์ด้วย

```bash
bash scripts/docker/export-runtime-assets.sh
```

จากนั้นนำไฟล์ `docker/runtime-assets/workflow-runtime-assets.tar.gz` ไปให้เพื่อน แล้วแตกที่ root ของโปรเจกต์ก่อนรัน Docker

```bash
tar -xzf docker/runtime-assets/workflow-runtime-assets.tar.gz
```

4. ถ้าต้องการแพ็กส่งต่อให้เพื่อนในคำสั่งเดียว

```bash
make docker-package
```

คำสั่งนี้จะ

- export DB ล่าสุดจากเครื่อง local ปัจจุบัน
- export runtime assets
- รวมเป็นไฟล์ส่งต่อเดียวที่ `docker/package/workflow-docker-package.tar.gz`

5. สตาร์ทระบบ

```bash
docker compose --env-file .env.docker up --build
```

หรือรันแบบ background

```bash
docker compose --env-file .env.docker up -d --build
```

## URL และพอร์ต

- Web: `http://127.0.0.1:8000`
- MariaDB on host: `127.0.0.1:3307`

ถ้าพอร์ตชน ให้แก้ใน `.env.docker`

## การ reset DB ให้ import dump ใหม่

MariaDB จะ import ไฟล์ใน `docker/mysql/initdb` เฉพาะตอน volume ยังว่าง

ถ้าต้องการ reset ให้ใช้

```bash
docker compose --env-file .env.docker down -v
docker compose --env-file .env.docker up -d --build
```

## Runtime files ที่ไม่อยู่ใน git

ตัว app ใช้ไฟล์ runtime บางส่วน เช่น

- `storage/uploads`
- `assets/img/profile`

ถ้าต้องการ environment ให้ตรงกับเครื่องต้นทางแบบเต็มจริง ๆ ต้องนำโฟลเดอร์เหล่านี้มาด้วย ไม่เช่นนั้น DB จะตรง แต่ไฟล์แนบบางรายการหรือรูปโปรไฟล์บางรายการอาจไม่มีไฟล์จริง

วิธีที่แนะนำคือใช้สคริปต์นี้บนเครื่องต้นทาง

```bash
make docker-runtime-assets
```

แล้วนำไฟล์ `docker/runtime-assets/workflow-runtime-assets.tar.gz` ไปแตกบนเครื่องปลายทางที่ root ของโปรเจกต์

## หมายเหตุ

- compose นี้ตั้งค่า app ให้ override DB connection ผ่าน env ของ container โดยไม่ชนกับ `.env` เดิม
- app container จะรัน `php scripts/migrate.php` ทุกครั้งหลัง DB พร้อม เพื่อให้ schema ตาม migrations ล่าสุด
