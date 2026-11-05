# Cloudflare Tunnel para o BOCA

Este arquivo prepara o `boca-docker` para publicar o BOCA usando Cloudflare Tunnel sem expor portas de entrada no host.

## O que ja esta pronto

- O BOCA web ja esta atendendo em `http://boca-web:80` dentro da rede Docker.
- O compose opcional [docker-compose.cloudflare.yml](C:\Users\Oxta\Desktop\boca-master\boca-docker\docker-compose.cloudflare.yml) sobe um container `cloudflared`.

## O que falta para `https://boca.caramelcoders.com`

1. O dominio `caramelcoders.com` precisa estar ativo na Cloudflare para publicar aplicacoes com hostname publico.
2. O subdominio `boca.caramelcoders.com` precisa poder ser substituido pelo tunnel.
3. E preciso criar um Tunnel no painel da Cloudflare e copiar o token.

## Passo a passo

1. Adicione `caramelcoders.com` na Cloudflare.
2. Troque os nameservers do dominio para os nameservers fornecidos pela Cloudflare.
3. No Zero Trust Dashboard, crie um Tunnel.
4. Crie um Public Hostname:
   - Hostname: `boca.caramelcoders.com`
   - Service type: `HTTP`
   - URL: `boca-web:80`
5. Coloque o token em uma variavel de ambiente local:

```powershell
$env:CLOUDFLARE_TUNNEL_TOKEN="cole-o-token-aqui"
```

6. Suba o stack com o compose adicional:

```powershell
docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.cloudflare.yml up -d
```

## Observacoes

- Nao publique o Grafana. O tunnel deve apontar apenas para o BOCA.
- Antes de expor o sistema, troque as credenciais fixas do projeto.
- Se quiser testar sem dominio primeiro, use um Quick Tunnel temporario com:

```powershell
docker run --rm cloudflare/cloudflared:latest tunnel --url http://host.docker.internal:8000
```
