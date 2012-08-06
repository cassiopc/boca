#!/bin/bash

if [ ! -x /etc/icpc/bocaserver.sh ]; then
  OK=1
  while [ "$OK" != "0" ]; do
    IP=`zenity --title="Setting up the BOCA server IP number" --text="Enter the IP address of the server (format x.y.w.z)\n\
If this is supposed to be the server, then leave it empty" --width=500 --height=100 --entry`
    [ "$IP" == "" ] && IP=LOCAL
    zenity --title="IP confirmation" --text="The chosen IP is $IP\nDo you confirm?" --question
    OK=$?
  done
  if [ "$IP" = "local" -o "$IP" = "LOCAL" ]; then
    IP=127.0.0.1
    BOCASERVER=0/0
  fi
  echo "BOCASERVER=$IP" > /etc/icpc/bocaserver.sh
  echo "$IP boca boca" >> /etc/hosts
  chmod 755 /etc/icpc/bocaserver.sh
fi
. /etc/icpc/bocaserver.sh

if [ ! -f /etc/icpc/.firsttimedone ]; then

  zenity --title="PAY ATTENTION TO THE FOLLOWING:" \
  --text="It is HIGHLY recommended that you set up a super-user password NOW. Set up the super-user password now?" --question
  OK=$?
  if [ "$OK" == "0" ]; then
    id -u icpcadmin 2>\dev\null >\dev\null
    if [ "$?" == "0" ]; then
    OK=1
    while [ $OK != 0 ]; do
    pass=`zenity --title="Setting up a icpcadmin password" --text="Take care \
 to keep it safe. icpcadmin is the user that \n\
 can become root using the command sudo, e.g. \n\
   sudo /bin/bash \n\
 TEAMS WILL USE THE ACCOUNT icpc, WITH PASSWORD\n\
 icpc. THEY MUST NOT KNOW THE PASSWORD YOU\n\
 ARE SETTING UP HERE, WHICH IS A PRIVILEGIED USER.\n\
 If you need to change the password later, you \n\
 must know the current password and use the \n\
 command-line passwd to change it. Do not forget it" --entry --hide-text`
    pass2=`zenity --title="Setting up a icpcadmin password" --text="Re-type it" --entry --hide-text`
    if [ "$pass" == "$pass2" -a "$pass" != "" ]; then
      OK=0
    else
      zenity --info --title="Error" --text="Passwords do not match"
    fi
    done
     pass=\$`/bin/echo -n "$pass2" | /usr/bin/makepasswd --clearfrom - --crypt-md5 | /usr/bin/cut -d'$' -f2-`
     pass2=""
     /usr/sbin/usermod -p "$pass" icpcadmin
     pass=`echo -n icpc | makepasswd --clearfrom - --crypt-md5 | cut -d'$' -f2-`
     pass=\$`echo $pass`
	 /usr/sbin/usermod -p "$pass" icpc
    zenity --info --title="Updated" --text="Password of icpcadmin should be updated\nIf not, login in and change it to something safe"
    else
      zenity --info --title="Update error" --text="User icpcadmin not found -- update your password by yourself." 
    fi
  fi

  if [ "$BOCASERVER" != "0/0" ]; then
  OK=1
  while [ $OK != 0 ]; do
  pass=`zenity --title="Setting up password of BOCA database" --text="Enter the password of the BOCA database IF AND ONLY IF \n\
this is NOT a team machine. The password must be entered \n\
in case this machine will be used for autojudging. \n\
If you do not enter a password now, it is possible to do \n\
it later using the command-line /etc/icpc/updatedbpass.sh \n\
IF THIS IS GOING TO BE USED BY A TEAM, THEN JUST \n\
PRESS ENTER WITHOUT TYPING ANY PASSWORD\n\
If this is a server, you may also leave this field empty" --entry --hide-text`
  if [ "$pass" == "" ]; then
    break
  fi
  pass2=`zenity --title="Setting up password of BOCA database" --text="Re-type it" --entry --hide-text`
  if [ "$pass" == "$pass2" -a "$pass" != "" ]; then
    OK=0
     /etc/icpc/updatedbpass.sh "$pass"
  else
   zenity --info --title="Error" --text="Passwords do not match"
  fi
  done
  fi
  touch /etc/icpc/.firsttimedone
  zenity --info --title="Setup completed" --text="The setup is completed. If you want to do it again, you might \n\
run the command-line /etc/icpc/restart.sh\nbut some files in this computer (e.g. from the icpc user) might be lost."
fi

if [ -x /etc/network/if-pre-up.d/boca ]; then
  . /etc/network/if-pre-up.d/boca
fi
