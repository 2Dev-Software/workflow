Workflow Docker handoff package

1. Copy .env.docker.example to .env.docker
2. Extract docker/runtime-assets/workflow-runtime-assets.tar.gz at the project root
3. Run: docker compose --env-file .env.docker up -d --build

Notes:
- Database seed comes from docker/mysql/initdb/001_deebuk_platform.sql
- Runtime files are required if you want uploads/profile images to match the source machine
