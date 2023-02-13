#! /bin/bash

# Update the version.txt file
gitVersion=".git/refs/heads/master"
versionString=$(cat "$gitVersion")
cat <<EOF > ./version.txt
$versionString
EOF

cp -R ./ ../Pixlee
cd ..

zip -r -X Pixlee_Pixlee-2.0.0.zip ./Pixlee/ -x './Pixlee/.git/*' -x './Pixlee/.gitignore' -x './Pixlee/packager.sh' -x './Pixlee/documentation/*'
rm -rf ./Pixlee
mv Pixlee_Pixlee-2.0.0.zip ./magento2/Pixlee_Pixlee-2.0.0.zip

