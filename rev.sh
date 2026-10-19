TARGET="http://20.42.115.173"
JAR=/tmp/coffee-cookies.txt

# 1. Log in as neil and save the session cookie
curl -s -c "$JAR" -b "$JAR" -o /dev/null -w "login: %{http_code}\n" \
  -d "username=neil&password=cappuccino" \
  "$TARGET/admin/login.php"

curl -s -c "$JAR" -b "$JAR" -o /dev/null -w "restore: %{http_code}\n" \
  -d "restore=1" "$TARGET/admin/"
