# syntax=docker/dockerfile:1.6
ARG NODE_VERSION=20
FROM node:${NODE_VERSION}-alpine

WORKDIR /app

# 更稳健的 npm 配置，避免网络抖动卡死
RUN npm config set fetch-timeout 600000 \
 && npm config set fetch-retry-maxtimeout 600000 \
 && npm config set prefer-offline true \
 && npm config set audit false \
 && npm config set fund false

# 如需镜像源（仅开发期、可选），去掉下一行注释：
# RUN npm config set registry https://registry.npmmirror.com/

# 先只拷贝清单文件，利用缓存层
COPY frontend/package*.json ./

# 使用 BuildKit 缓存挂载，加速 npm ci
RUN --mount=type=cache,target=/root/.npm \
    npm ci

# 再拷贝剩余源码
COPY frontend ./

EXPOSE 5173
