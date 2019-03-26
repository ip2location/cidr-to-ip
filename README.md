# CIDR to IP Range

This PHP script converts CIDR into IP range (dotted format) in a CSV file. This converter only support IPv4 conversion.



## Usage

``` bash
php cidr-to-ip.php {INPUT_FILE} {OUTPUT_FILE}
```

**Example:**

```
php cidr-to-ip.php ip-cidr.csv ip-cidr-new.csv
```



### Sample Input

```
"1.16.0.0/18","KR","Korea, Republic of"
"1.20.0.0/16","TH","Thailand"
"150.101.32.66/32","AU","Australia"
"212.214.138.0/28","SE","Sweden"
"216.224.227.128/27","US","United States"
```



### Sample Output

```
"1.16.0.0","1.16.63.255","16384","KR","Korea, Republic of"
"1.20.0.0","1.20.255.255","65536","TH","Thailand"
"150.101.32.66","150.101.32.66","1","AU","Australia"
"212.214.138.0","212.214.138.15","16","SE","Sweden"
"216.224.227.128","216.224.227.159","32","US","United States"
```

## Support
URL: https://www.ip2location.com

