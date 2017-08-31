#!/bin/bash
OUTPUT=/tmp/`hostname -s`
echo $OUTPUT

echo '##### hostname' > $OUTPUT
hostname >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /etc/redhat-release' >> $OUTPUT
cat /etc/redhat-release >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /proc/cpuinfo |grep processor |wc -l' >> $OUTPUT
cat /proc/cpuinfo |grep processor |wc -l >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /proc/meminfo |grep MemTotal' >> $OUTPUT
cat /proc/meminfo |grep MemTotal >> $OUTPUT

echo >> $OUTPUT
echo '##### ifconfig -a' >> $OUTPUT
ifconfig -a >> $OUTPUT

echo >> $OUTPUT
echo '##### df' >> $OUTPUT
df >> $OUTPUT

echo >> $OUTPUT
echo '##### ps -efawww' >> $OUTPUT
ps -efawww >> $OUTPUT

echo >> $OUTPUT
echo '##### ss -lnptu' >> $OUTPUT
ss -lnptu >> $OUTPUT

echo >> $OUTPUT
echo '##### lsof |egrep -i "tcp|udp"' >> $OUTPUT
lsof |egrep -i "tcp|udp" >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /etc/hosts' >> $OUTPUT
cat /etc/hosts >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /etc/passwd' >> $OUTPUT
cat /etc/passwd >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /etc/group' >> $OUTPUT
cat /etc/group >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /etc/sudoers' >> $OUTPUT
cat /etc/sudoers >> $OUTPUT

echo >> $OUTPUT
echo '##### ls /var/spool/cron/' >> $OUTPUT
ls /var/spool/cron/ >> $OUTPUT
for user in `ls /var/spool/cron/`
do
 echo >> $OUTPUT
 echo '##### cat /var/spool/cron/'$user >> $OUTPUT
 cat /var/spool/cron/$user >> $OUTPUT
done

echo >> $OUTPUT
echo '##### lsof |egrep "_log|\.log|\.txt|\.out"' >> $OUTPUT
lsof |egrep "_log|\.log|\.txt|\.out" >> $OUTPUT

echo >> $OUTPUT
echo '##### lsof |egrep "_log|\.log|\.txt|\.out"    |awk '{print $NF}' |sort|uniq' >> $OUTPUT
lsof |egrep "_log|\.log|\.txt|\.out"    |awk '{print $NF}' |sort|uniq >> $OUTPUT

echo >> $OUTPUT
echo '##### rpm -qa |sort' >> $OUTPUT
rpm -qa |sort >> $OUTPUT

echo >> $OUTPUT
echo '##### cat /usr/local/nagios/etc/nrpe.cfg"' >> $OUTPUT
cat /usr/local/nagios/etc/nrpe.cfg >> $OUTPUT
