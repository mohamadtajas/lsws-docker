#!/bin/bash
LSDIR='/usr/local/lsws'
LSWS_SERIAL='AKUn-1SIU-WNQj-CtHC'

if [ -z "$(ls -A -- "${LSDIR}/conf/")" ]; then
	cp -R ${LSDIR}/.conf/* ${LSDIR}/conf/
fi
if [ -z "$(ls -A -- "${LSDIR}/admin/conf/")" ]; then
	cp -R ${LSDIR}/admin/.conf/* ${LSDIR}/admin/conf/
fi

if [ -n "$LSWS_SERIAL" ]; then
    echo "Injecting custom license serial..."
    # Write the provided serial number into serial.no
    echo "$LSWS_SERIAL" > ${LSDIR}/conf/serial.no
    # Run the registration command (re-register the license)
    ${LSDIR}/bin/lshttpd -r
    # Verify the registration (this should result in a new license.key)
    ${LSDIR}/bin/lshttpd -V
    # Restart LSWS to apply the new license
    ${LSDIR}/bin/lswsctrl restart
else
    # Fallback to trial license if no custom serial provided
    if [ ! -e ${LSDIR}/conf/serial.no ] && [ ! -e ${LSDIR}/conf/license.key ]; then
        rm -f ${LSDIR}/conf/trial.key*
        wget -P ${LSDIR}/conf/ http://license.litespeedtech.com/reseller/trial.key
    fi
fi

# if [ ! -e ${LSDIR}/conf/serial.no ] && [ ! -e ${LSDIR}/conf/license.key ]; then
#     rm -f ${LSDIR}/conf/trial.key*
#     wget -P ${LSDIR}/conf/ http://license.litespeedtech.com/reseller/trial.key
# fi
chown 994:994 ${LSDIR}/conf/ -R
chown 994:1001 ${LSDIR}/admin/conf/ -R

/usr/local/lsws/bin/lswsctrl start
$@
while true; do
	if ! ${LSDIR}/bin/lswsctrl status | grep 'litespeed is running with PID *' > /dev/null; then
		break
	fi
	sleep 60
done