-- Drop unused permission mapping tables.
-- Reason: current RBAC implementation uses dh_roles + dh_user_roles only.
-- Safety: use IF EXISTS and drop child table first due foreign key dependency.

DROP TABLE IF EXISTS `dh_role_permissions`;
DROP TABLE IF EXISTS `dh_permissions`;
