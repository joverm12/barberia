from django.db.backends.mysql.base import DatabaseWrapper as MySQLDatabaseWrapper

class DatabaseWrapper(MySQLDatabaseWrapper):
    def get_server_version(self):
        # Le decimos a Django que tenemos una versión superior a la 10.6
        return (10, 6, 0)