from ipaddress import *

c=0
net = ip_network('204.16.168.0/255.255.248.0')
for i in net:
    ip=f'{i:b}'
    if ip.count('1') % 5 != 0:
        c+=1
print(c)