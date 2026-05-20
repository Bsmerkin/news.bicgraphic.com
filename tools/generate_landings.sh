#!/bin/bash
# Script para generar landings y thank you pages multilingües a partir de plantillas base
# Uso: ./generate_landings.sh

# Configuración de idiomas y textos
IDIOMAS=(es en fr)
# Puedes añadir más idiomas y textos aquí

# Textos por idioma (ejemplo)
declare -A TITLE=( [es]="¡Gracias por tu pedido!" [en]="Thank you for your order!" [fr]="Merci pour votre commande!" )
declare -A INTRO=( [es]="Por favor, completa esta breve encuesta para ayudarnos a mejorar." [en]="Please fill out this short survey to help us improve." [fr]="Merci de remplir ce court sondage pour nous aider à nous améliorer." )
declare -A LEGAL=( [es]="Esta página es solo informativa. No respondas a este mensaje." [en]="This page is for information only. Do not reply to this message." [fr]="Cette page est informative. Ne répondez pas à ce message." )
declare -A COPYRIGHT=( [es]="© 2026 BIC Graphic. Todos los derechos reservados." [en]="© 2026 BIC Graphic. All rights reserved." [fr]="© 2026 BIC Graphic. Tous droits réservés." )

# Plantillas base
LANDING_TEMPLATE="landing-encuesta-bonita.html"
THANKYOU_TEMPLATE="landing-thankyou.html"

# Carpeta de salida
OUTPUT_DIR="landings-final"
mkdir -p "$OUTPUT_DIR"

for lang in "${IDIOMAS[@]}"; do
  # Generar landing
  sed \
    -e "s/{trans_landing_title}/${TITLE[$lang]}/g" \
    -e "s/{trans_landing_intro}/${INTRO[$lang]}/g" \
    -e "s/{trans_legal}/${LEGAL[$lang]}/g" \
    -e "s/{trans_copyright}/${COPYRIGHT[$lang]}/g" \
    "$LANDING_TEMPLATE" > "$OUTPUT_DIR/landing-encuesta-$lang.html"

  # Generar thank you page
  sed \
    -e "s/{trans_landing_title}/${TITLE[$lang]}/g" \
    -e "s/{trans_landing_intro}/${INTRO[$lang]}/g" \
    -e "s/{trans_legal}/${LEGAL[$lang]}/g" \
    -e "s/{trans_copyright}/${COPYRIGHT[$lang]}/g" \
    "$THANKYOU_TEMPLATE" > "$OUTPUT_DIR/landing-thankyou-$lang.html"
done

echo "Landings generadas en $OUTPUT_DIR/ para: ${IDIOMAS[*]}"
