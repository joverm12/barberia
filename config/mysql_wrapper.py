from django.db.backends.mysql.base import DatabaseWrapper as MySQLDatabaseWrapper

class DatabaseWrapper(MySQLDatabaseWrapper):
    def get_server_version(self):
        # Engañamos a Django diciéndole que tenemos una versión 10.6
        return (10, 6, 0)