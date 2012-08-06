#include <stdio.h>
int main(void) {
  int i, j, n, max, inst=0;
  while(42) {
    scanf("%d", &n);
    if(!n) break;
    max=0;
    for(i=0; i<n; i++) {
      scanf("%d", &j);
      if(j>max) max=j;
    }
    for(i=0; max>0; i++)
      max >>= 1;
    printf("Instancia %d\n%d\n\n", ++inst, i*n);
  }
  return 0;
}
