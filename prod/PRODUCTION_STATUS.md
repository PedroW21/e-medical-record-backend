# Production Server - vscribo-server

**Host:** 217.216.95.27 (Contabo VPS)
**OS:** Ubuntu 24.04.4 LTS (Noble Numbat)
**SSH Alias:** `ssh vscribo-server`
**User:** verner (sudo, key-only auth)
**Setup Date:** 2026-03-10

---

## Security Hardening (Completed)

### SSH Hardening (`/etc/ssh/sshd_config.d/hardening.conf`)

| Diretiva | Valor | Finalidade |
|---|---|---|
| PermitRootLogin | no | Bloqueia login root via SSH |
| PasswordAuthentication | no | Somente chave SSH, sem senha |
| PermitEmptyPasswords | no | Bloqueia contas sem senha |
| PubkeyAuthentication | yes | Autenticação por chave pública |
| AllowUsers | verner | Whitelist de usuários permitidos |
| X11Forwarding | no | Desabilita forwarding gráfico |
| MaxAuthTries | 3 | Desconecta após 3 tentativas falhadas |
| MaxSessions | 5 | Máximo 5 sessões simultâneas |
| ClientAliveInterval | 300 | Ping a cada 5min pra checar cliente |
| ClientAliveCountMax | 2 | Desconecta após 10min ocioso |
| KbdInteractiveAuthentication | no | Desabilita auth interativa por teclado |

### Firewall — UFW

Política padrão: **deny incoming, allow outgoing**

| Porta | Protocolo | Serviço |
|---|---|---|
| 22 | TCP | SSH |
| 80 | TCP | HTTP |
| 443 | TCP | HTTPS |

### fail2ban (`/etc/fail2ban/jail.local`)

| Parâmetro | Valor | Finalidade |
|---|---|---|
| bantime | 3600 | Ban de 1 hora por IP |
| findtime | 600 | Janela de 10min pra contar tentativas |
| maxretry (SSH) | 3 | Ban após 3 tentativas falhadas |

### Kernel Hardening (`/etc/sysctl.d/99-hardening.conf`)

**Rede:**
| Diretiva | Valor | Finalidade |
|---|---|---|
| ip_forward | 0 | Desabilita encaminhamento de pacotes (server não é roteador) |
| send_redirects | 0 | Impede envio de ICMP redirects |
| accept_redirects | 0 | Ignora redirects recebidos (IPv4 e IPv6) |
| secure_redirects | 0 | Ignora até redirects de gateways conhecidos |
| log_martians | 1 | Loga pacotes com IPs impossíveis (sinal de spoofing) |
| icmp_ignore_bogus_error_responses | 1 | Ignora mensagens ICMP de erro malformadas |

**SYN Flood Protection:**
| Diretiva | Valor | Finalidade |
|---|---|---|
| tcp_syncookies | 1 | Não reserva memória até conexão ser confirmada |
| tcp_max_syn_backlog | 2048 | Limite da fila de conexões SYN pendentes |
| tcp_synack_retries | 2 | Desiste rápido de conexões incompletas |

**Memória e Kernel:**
| Diretiva | Valor | Finalidade |
|---|---|---|
| randomize_va_space | 2 | ASLR máximo — randomiza endereços de memória |
| kptr_restrict | 2 | Esconde endereços de memória do kernel |
| dmesg_restrict | 1 | Só root lê logs do kernel |
| sysrq | 0 | Desabilita Magic SysRq key |

**File System:**
| Diretiva | Valor | Finalidade |
|---|---|---|
| protected_symlinks | 1 | Previne ataques via symlinks em /tmp |
| protected_hardlinks | 1 | Previne ataques via hardlinks |
| protected_fifos | 2 | Proteção pra pipes nomeados |
| protected_regular | 2 | Proteção pra arquivos em dirs world-writable |
| suid_dumpable | 0 | Impede core dumps de programas SUID |

---

## Application Stack (Installed)

| Software | Versão | Instalação |
|---|---|---|
| PHP | 8.5.3 | PPA ondrej/php |
| Nginx | 1.24.0 | apt (Ubuntu) |
| Node.js | 22.22.1 | NodeSource |
| pnpm | 10.32.0 | Standalone script (get.pnpm.io) |
| Composer | 2.9.5 | getcomposer.org installer |
| Git | 2.43.0 | apt (Ubuntu) |
| Redis | 7.0.15 | apt (Ubuntu) |
| PostgreSQL | 16.13 | apt (Ubuntu) |

### Serviços habilitados (systemd)
- `nginx`
- `php8.5-fpm`
- `redis-server`
- `postgresql`
- `fail2ban`
- `ufw`

---

## SSH Config Local (`~/.ssh/config`)

```
Host vscribo-server
    HostName 217.216.95.27
    User verner
    IdentityFile ~/.ssh/id_ed25519
```
