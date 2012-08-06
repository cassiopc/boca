#include <iostream>
using namespace std;
int main(void) {
  int i, j, n, max, inst=0;
  while(42) {
    cin >> n;
    if(!n) break;
    max=1;
    for(i=0; i<n; i++) {
      cin >> j;
      if(j>max) max=j;
    }
    for(i=0; max>0; i++)
      max >>= 1;
    cout << "Instancia " << ++inst << endl << (i*n) << endl << endl;
  }
  return 0;
}
