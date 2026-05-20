import requests
import sys

# CONFIGURACIÓN (Copiada de mautic_multilang_clone.py)
MAUTIC_URL = 'https://news.bicgraphic.com/'
CLIENT_ID = '2_2tqpa8ul56o00k0oc8os4o0o88o84gscgw4k0ck0kwwokccw80'
CLIENT_SECRET = '8aa5vww4stwcow4ks8884o8g8gg0so480s08owss0oc4skgs'
USERNAME = 'barbara.smerkin@bicworld.com'
PASSWORD = 'U3cajJ94nKW7KT'
REDIRECT_URI = 'http://localhost'

def test_mautic_login():
    token_url = f"{MAUTIC_URL}/oauth/v2/token"
    data = {
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'grant_type': 'password',
        'username': USERNAME,
        'password': PASSWORD,
        'redirect_uri': REDIRECT_URI
    }
    print(f"Intentando autenticación con {MAUTIC_URL}...")
    try:
        resp = requests.post(token_url, data=data)
        if resp.status_code == 200:
            print("¡Éxito! Autenticación correcta.")
            access_token = resp.json().get('access_token')
            if access_token:
                print("Token de acceso obtenido correctamente.")
                # Probar una llamada simple a la API
                session = requests.Session()
                session.headers.update({'Authorization': f'Bearer {access_token}'})
                api_test_url = f"{MAUTIC_URL}/api/pages?limit=1"
                api_resp = session.get(api_test_url)
                if api_resp.status_code == 200:
                    print("Conexión a la API verificada correctamente.")
                else:
                    print(f"Error al conectar con la API: {api_resp.status_code}")
                    print(api_resp.text)
            else:
                print("Error: No se encontró 'access_token' en la respuesta.")
        else:
            print(f"Error de autenticación: Código de estado {resp.status_code}")
            print(resp.text)
    except Exception as e:
        print(f"Ocurrió un error inesperado: {e}")

if __name__ == "__main__":
    test_mautic_login()
