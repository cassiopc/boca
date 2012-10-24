program bits;
var i, j, n, max, inst: Longint;
begin
  inst:=0;
  while true do
  begin
    read(n);
    if n=0 then break;
    max:=1;
    for i:=0 to n-1 do
    begin
      read(j);
      if j>max then max:=j
    end;
    i:=0;
    while max>0 do
    begin
      max := max div 2;
      i := i + 1
    end;
    write('Instacia ');
    inst := inst + 1;
    writeln(inst);
    writeln(i*n);
    writeln()
  end
end.
