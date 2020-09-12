#!/bin/bash

projectPath="$(dirname "${0}")/.."
cwd="$(pwd)"
cd "${projectPath}" && projectPath="$(pwd)" && cd "${cwd}" 

	"${projectPath}/vendor/noresources/ns-xml/ns/sh/build-php.sh" \
	--xml-description "${projectPath}/resources/cli/tools/postgresql-typeregistry-trait.xml" \
	--base \
	-o "${projectPath}/tools/lib/Parser.php" \
	--parser-namespace NoreSources

for name in postgresql-typeregistry-trait
do
	"${projectPath}/vendor/noresources/ns-xml/ns/sh/build-php.sh" \
		--xml-description "${projectPath}/resources/cli/tools/${name}.xml" \
		--info \
		-o "${projectPath}/tools/lib/${name}.php" \
		--parser-namespace NoreSources
done
	
#	--merge resources/cli/tools/.php