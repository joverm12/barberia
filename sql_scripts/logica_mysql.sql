-- 1. FUNCION: Calcula un recargo fijo por servicios especiales
DELIMITER $$
CREATE FUNCTION calcular_recargo(precio DECIMAL(10,2)) 
RETURNS DECIMAL(10,2)
DETERMINISTIC 
BEGIN
    RETURN precio * 1.05;
END $$
DELIMITER ;

-- 2. PROCEDIMIENTO: Cuenta cuántas citas tiene un cliente
DELIMITER $$
CREATE PROCEDURE contar_citas_cliente(IN cliente_id_in INT, OUT total INT) 
BEGIN 
    SELECT COUNT(*) INTO total FROM cuentas_cita WHERE cliente_id = cliente_id_in; 
END $$
DELIMITER ;

-- 3. TRIGGER: Registra auditoría cuando se crea un usuario
DELIMITER $$
CREATE TRIGGER after_insert_usuario 
AFTER INSERT ON cuentas_usuario 
FOR EACH ROW 
BEGIN 
    INSERT INTO cuentas_logsistema(usuario_correo, accion, modulo, fecha) 
    VALUES (NEW.correo, 'NUEVO_USUARIO', 'ADMIN', NOW()); 
END $$
DELIMITER ;