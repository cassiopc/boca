#include<stdlib.h>
#include<stdio.h>
#include<sys/types.h>
#include<unistd.h>
char str[10000];
int main(int argc, char **argv) {
  if(argc != 8) return 1;
  sprintf(str,"/usr/bin/boca-submit-run-root %1000s %1000s %1000s %1000s %1000s %1000s %1000s",argv[1],argv[2],argv[3],argv[4],argv[5],argv[6],argv[7]);
  setuid(0);
  system(str);
  return 0;
}
