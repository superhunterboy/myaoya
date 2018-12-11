set APP_HOME=%~dp0
set JAVA_EXEC=java

set CP=%APP_HOME%
set CP=%CP%;%APP_HOME%lib\*

start %JAVA_EXEC% -Xmx512M -XX:MaxPermSize=192M -cp "%CP%" com.yeepay.g3.utils.security.cfca.rs.RestMain %*