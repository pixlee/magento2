#! /bin/bash

# Update the version.txt file
gitVersion=".git/refs/heads/master"
versionString=$(cat "$gitVersion")
cat <<EOF > ./version.txt
$versionString
EOF

cp -R ./ ../Pixlee
cd ..
# Obviously, don't distribute .git files, and don't include this file
# At the time of writing this (2016-05-09), the README.md file is empty and useless
# Finally, don't include the 'documentation' directory, because it's not needed as part of
# the distribution. It's just convenient to keep it in the same directory as the source.
zip -r -X Pixlee_Pixlee-2.0.0.zip ./Pixlee/ -x './Pixlee/.git/*' -x './Pixlee/.gitignore' -x './Pixlee/README.md' -x './Pixlee/packager.sh' -x './Pixlee/documentation/*'
rm -rf ./Pixlee
mv Pixlee_Pixlee-2.0.0.zip ./magento2/Pixlee_Pixlee-2.0.0.zip

