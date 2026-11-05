"""Pinned TLS transport shared by the root client and the desk hub."""
import hashlib
import hmac
import http.client
import ipaddress
import json
import ssl


def address(value):
    ip = ipaddress.IPv4Address(value)
    if ip.is_unspecified or ip.is_loopback or ip.is_multicast or str(ip) == '255.255.255.255':
        raise ValueError('Informe o IPv4 da banca na rede do laboratorio')
    return str(ip)


def post(ip, fingerprint, path, data, *, token=None, port=8443):
    ip = address(ip)
    # Authentication is an exact certificate pin, checked BEFORE HTTP credentials
    # are sent. It intentionally works offline without DNS, a CA or wall clock.
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    context.check_hostname = False
    context.verify_mode = ssl.CERT_NONE
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    connection = http.client.HTTPSConnection(ip, port, context=context, timeout=5)
    try:
        connection.connect()
        actual = hashlib.sha256(connection.sock.getpeercert(binary_form=True)).hexdigest()
        if not hmac.compare_digest(actual, fingerprint):
            raise ValueError('Certificado nao pertence a banca deste laboratorio')
        local_ip = connection.sock.getsockname()[0]
        headers = {'Content-Type': 'application/json'}
        if token:
            headers['Authorization'] = 'Bearer '+token
        connection.request('POST', path, json.dumps(data).encode(), headers)
        response = connection.getresponse()
        payload = response.read(2*1024*1024+1)
        if response.status != 200 or len(payload) > 2*1024*1024:
            raise ValueError('Banca recusou o pedido; solicite novamente ou procure a organizacao')
        return json.loads(payload), local_ip
    finally:
        connection.close()
