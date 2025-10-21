# ===============================
#   FRONTEND DOCKERFILE (VITE + NGINX)
# ===============================

# ---------- Build Stage ----------
FROM node:22-alpine AS build
WORKDIR /app

# Copy dependency manifests
COPY package*.json ./

# Install dependencies cleanly (fallback for local env)
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund

# Copy source files
COPY . .

# Build production assets
RUN npm run build

# ---------- Serve Stage ----------
FROM nginx:alpine

# Copy built dist into nginx
COPY --from=build /app/dist /usr/share/nginx/html

# Copy Nginx config file (make sure it exists at ./frontend/nginx.conf)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose the container port
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
