#include<stdlib.h>
#include<stdio.h>
#include<sys/types.h>
#include<unistd.h>
char str[3000];
char *clean(char *s) {
  int i;
  if(s[0]=='"') s++;
  for(i=0; i < 299 && s[i]; ++i) {
    if(s[i] == '"' ||
       s[i] == '\\' ||
       s[i] == '$' ||
       s[i] == '`') {
      if(s[i+1] == 0) s[i]=0;
      else s[i]='_';
    }
  }
  if(i >= 299) s[i]=0;
  return s;
}
int main(int argc, char **argv) {
  if(argc != 8) return 1;
  sprintf(str,"/usr/bin/boca-submit-run-root \"%s\" \"%s\" \"%s\" \"%s\" \"%s\" \"%s\" \"%s\"",
	  clean(argv[1]),clean(argv[2]),clean(argv[3]),clean(argv[4]),clean(argv[5]),clean(argv[6]),clean(argv[7]));
  setuid(0);
  system(str);
  return 0;
}
