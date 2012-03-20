<?php
/**
 * This file is automatically generated. Lint this module to rebuild it.
 * @generated
 */



phutil_require_module('phabricator', 'aphront/response/404');
phutil_require_module('phabricator', 'aphront/response/redirect');
phutil_require_module('phabricator', 'applications/maniphest/constants/priority');
phutil_require_module('phabricator', 'applications/maniphest/constants/status');
phutil_require_module('phabricator', 'applications/maniphest/constants/transactiontype');
phutil_require_module('phabricator', 'applications/maniphest/controller/base');
phutil_require_module('phabricator', 'applications/maniphest/query');
phutil_require_module('phabricator', 'applications/maniphest/storage/task');
phutil_require_module('phabricator', 'applications/maniphest/storage/taskproject');
phutil_require_module('phabricator', 'applications/maniphest/storage/transaction');
phutil_require_module('phabricator', 'applications/phid/handle/data');
phutil_require_module('phabricator', 'infrastructure/celerity/api');
phutil_require_module('phabricator', 'infrastructure/javelin/api');
phutil_require_module('phabricator', 'infrastructure/javelin/markup');
phutil_require_module('phabricator', 'storage/qsprintf');
phutil_require_module('phabricator', 'storage/queryfx');
phutil_require_module('phabricator', 'view/control/table');
phutil_require_module('phabricator', 'view/form/base');
phutil_require_module('phabricator', 'view/form/control/submit');
phutil_require_module('phabricator', 'view/form/control/togglebuttons');
phutil_require_module('phabricator', 'view/form/control/tokenizer');
phutil_require_module('phabricator', 'view/layout/listfilter');
phutil_require_module('phabricator', 'view/layout/panel');
phutil_require_module('phabricator', 'view/layout/sidenavfilter');
phutil_require_module('phabricator', 'view/utils');

phutil_require_module('phutil', 'markup');
phutil_require_module('phutil', 'parser/uri');
phutil_require_module('phutil', 'utils');


phutil_require_source('ManiphestReportController.php');