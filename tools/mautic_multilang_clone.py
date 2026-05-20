import requests
import copy

# CONFIGURACIÓN

# --- CONFIGURACIÓN OAUTH2 ---
MAUTIC_URL = 'https://news.bicgraphic.com/'  # Cambia por tu URL
IDIOMAS = ['es', 'de', 'fr', 'it', 'nl', 'pl', 'pt']

# Traducciones email (ejemplo, completa según tu necesidad)
EMAIL_TEXTS = {
    'es': {
        'titulo': '¡Nos encantaría conocer tu opinión sobre tu primer pedido!',
        'intro': '¡Gracias por realizar tu primer pedido con nosotros!',
        'cuerpo': 'Nos gustaría saber cómo fue tu experiencia para seguir mejorando nuestro servicio.\nPor eso, te invitamos a compartir tu opinión respondiendo 4 preguntas rápidas.',
        'boton': 'Ir a la encuesta',
        'cierre': 'Muchas gracias por tu tiempo y colaboración.<br><br>Un cordial saludo,<br>Equipo BIC Graphic',
    },
    # ... añade el resto de idiomas aquí ...
}


# --- AUTENTICACIÓN OAUTH2 (password grant) ---
def mautic_login():
    token_url = f"{MAUTIC_URL}/oauth/v2/token"
    data = {
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'grant_type': 'password',
        'username': USERNAME,
        'password': PASSWORD,
        'redirect_uri': REDIRECT_URI
    }
    resp = requests.post(token_url, data=data)
    resp.raise_for_status()
    access_token = resp.json()['access_token']
    session = requests.Session()
    session.headers.update({'Authorization': f'Bearer {access_token}'})
    return session

# --- FUNCIONES DE CLONADO Y TRADUCCIÓN ---
def clone_landing(session, base_id, lang, textos):
    # 1. Obtener datos base
    r = session.get(f"{MAUTIC_URL}/api/pages/{base_id}")
    r.raise_for_status()
    base = r.json()['page']
    # 2. Modificar textos
    base['title'] = textos['titulo']
    base['description'] = textos.get('intro', base.get('description', ''))
    # ...otros campos...
    base['language'] = lang
    # 3. Crear nueva landing
    r = session.post(f"{MAUTIC_URL}/api/pages/new", json=base)
    r.raise_for_status()
    return r.json()['page']['id']

def clone_form(session, base_id, lang, textos, thankyou_id):
    r = session.get(f"{MAUTIC_URL}/api/forms/{base_id}")
    r.raise_for_status()
    base = r.json()['form']
    base['name'] += f" ({lang})"
    base['language'] = lang
    # Traducir labels, placeholders, etc. aquí si tienes los textos
    # Asignar acción de submit
    for action in base['actions']:
        if action['type'] == 'redirect':
            action['redirectUrl'] = f"{MAUTIC_URL}/landing-thankyou-{lang}.html"
    r = session.post(f"{MAUTIC_URL}/api/forms/new", json=base)
    r.raise_for_status()
    return r.json()['form']['id']

def clone_email(session, base_id, lang, textos):
    r = session.get(f"{MAUTIC_URL}/api/emails/{base_id}")
    r.raise_for_status()
    base = r.json()['email']
    base['subject'] = textos['titulo']
    base['customHtml'] = f"<h1>{textos['intro']}</h1><p>{textos['cuerpo']}</p><a href='{{pagelink}}'>{textos['boton']}</a><p>{textos['cierre']}</p>"
    base['language'] = lang
    r = session.post(f"{MAUTIC_URL}/api/emails/new", json=base)
    r.raise_for_status()
    return r.json()['email']['id']

# --- SCRIPT PRINCIPAL ---
def main():
    session = mautic_login()
    # 1. Clonar y traducir landings thank you
    thankyou_ids = {}
    for lang in IDIOMAS:
        textos = EMAIL_TEXTS.get(lang, EMAIL_TEXTS['es'])
        thankyou_ids[lang] = clone_landing(session, 6, lang, textos)
    # 2. Clonar y traducir formularios
    form_ids = {}
    for lang in IDIOMAS:
        textos = EMAIL_TEXTS.get(lang, EMAIL_TEXTS['es'])
        form_ids[lang] = clone_form(session, 4, lang, textos, thankyou_ids[lang])
    # 3. Clonar y traducir landings con formulario
    for lang in IDIOMAS:
        textos = EMAIL_TEXTS.get(lang, EMAIL_TEXTS['es'])
        clone_landing(session, 5, lang, textos)
    # 4. Clonar y traducir emails
    for lang in IDIOMAS:
        textos = EMAIL_TEXTS.get(lang, EMAIL_TEXTS['es'])
        clone_email(session, 217, lang, textos)
    print("¡Proceso completado!")

if __name__ == "__main__":
    main()
