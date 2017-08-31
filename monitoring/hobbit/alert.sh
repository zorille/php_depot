#!/usr/local/bin/bash
DOSSIER_CONFIG=/home/hobbit/server/etc/config

function envoi_mail
{
 INFO=$1
 COLOR=$2
 #echo "$BBALPHAMSG" | ${MAIL} "Hobbit [${ACKCODE}] ${BBHOSTSVC} ${INFO} (${COLOR})" tech-exploitation@client.com 
 echo "$BBALPHAMSG" | ${MAIL} "Hobbit [${ACKCODE}] ${BBHOSTSVC} ${INFO} (${COLOR})" dv@client.com 

 return 0
}

function envoi_sms
{
 # envoi d'un sms via nagios
 echo "2|${INFO} `${DATE} +%H:%M:%S` : ${BBHOSTSVC} est en ERREUR" > ${DOSSIER_CONFIG}/nagios_etat.log

 return 0
}
#env >${DOSSIER_CONFIG}/tempo.txt

if [ "${RECOVERED}" == "1" ]
then
 envoi_mail RECOVERED GREEN
 echo "0|Tout est OK dans Hobbit" > ${DOSSIER_CONFIG}/nagios_etat.log
 exit 0
fi

for ligne in `cat ${DOSSIER_CONFIG}/${BBHOSTNAME}_config |egrep -v "^#"`
do
 MONITEUR=`echo ${ligne} | cut -d : -f 3`
 if [ "${MONITEUR}" == "${BBSVCNAME}" ]
 then
  NIVEAU=`echo ${ligne} | cut -d : -f 2`
  case ${NIVEAU} in
  1)
	 envoi_mail CRITICAL RED
	 envoi_sms
   ;;
  2)
	 envoi_mail CRITICAL RED
   ;;
  3)
	 envoi_mail WARNING YELLOW
   ;;
  *)
   echo erreur
   ;;
  esac
 fi
done
 
