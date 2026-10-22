FROM node:22-bookworm-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
      python3 python3-venv curl ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Pin a specific version once you've tested one, e.g. --build-arg CLAUDE_CODE_VERSION=x.y.z
ARG CLAUDE_CODE_VERSION=latest
RUN npm install -g @anthropic-ai/claude-code@${CLAUDE_CODE_VERSION}

RUN python3 -m venv /opt/venv && /opt/venv/bin/pip install --no-cache-dir \
      "mcp[cli]<2" azure-identity azure-mgmt-network azure-monitor-query

WORKDIR /app
COPY suricata_nsg_mcp.py mcp.json prompt.md entrypoint.sh ./
RUN chmod +x entrypoint.sh && mkdir -p /app/logs && chown -R node:node /app

ENV DISABLE_AUTOUPDATER=1 \
    CLAUDE_CODE_DISABLE_NONESSENTIAL_TRAFFIC=1

USER node
ENTRYPOINT ["/app/entrypoint.sh"]