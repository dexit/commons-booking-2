#!/bin/sh
perl -0pi -w -e "s/ DEFAULT CHARSET=[a-z0-9]+ *;/;/g;" dump.sql
perl -0pi -w -e "s/\/\*![0-9]+ +SET NAMES [a-z0-9]+ \*\/ *;//g;" dump.sql
perl -0pi -w -e "s/\/\*![0-9]+ +SET character_set_client += +[a-z0-9_]+ *\*\/ *;//g;" dump.sql
perl -0pi -w -e "s/\/\*![0-9]+ +SET character_set_results += +[a-z0-9_]+ *\*\/ *;//g;" dump.sql
perl -0pi -w -e "s/\/\*![0-9]+ +SET collation_connection  += +[a-z0-9_]+ *\*\/ *;//g;" dump.sql
perl -0pi -w -e "s/\/\*![0-9]+ +DEFINER=\`root\`@\`127\.0\.0\.1\`\*\///g;" dump.sql
perl -0pi -w -e "s/ DEFINER=\`root\`@\`127\.0\.0\.1\`/ /g;" dump.sql
perl -0pi -w -e "s/SET character_set_client += +[a-z0-9_]+ *;//g;" dump.sql
