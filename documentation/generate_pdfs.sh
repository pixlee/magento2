#!/bin/bash
pandoc pixlee_magento2_extension_specification.md --template pixlee-markdown.latex --latex-engine=xelatex --toc --output pixlee_magento2_extension_specification.pdf

pandoc pixlee_magento2_user_guide.md --template pixlee-markdown.latex --latex-engine=xelatex --toc --output pixlee_magento2_user_guide.pdf

pandoc pixlee_magento2_release_notes.md --template pixlee-markdown.latex --latex-engine=xelatex --toc --output pixlee_magento2_release_notes.pd

pandoc pixlee_magento2_test_documentation.md --template pixlee-markdown.latex --latex-engine=xelatex --toc --output pixlee_magento2_test_documentation.pdf
