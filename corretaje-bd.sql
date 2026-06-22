CREATE SCHEMA IF NOT EXISTS `Corretaje`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE `Corretaje`;

-- ------------------------------------------------------------
-- TABLAS DE CATÁLOGO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Nacionalidad` (
  `id`     INT         NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
);

INSERT INTO `Nacionalidad` (nombre) VALUES
('Chilena'),
('Venezolana'),
('Peruana'),
('Colombiana'),
('Haitiana'),
('Boliviana'),
('Argentina'),
('Ecuatoriana'),
('Paraguaya'),
('Uruguaya'),
('Brasileña');

CREATE TABLE IF NOT EXISTS `Banco` (
  `id`     INT         NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(60) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
);

INSERT INTO `Banco` (nombre) VALUES
('Banco de Chile'),
('Banco Santander'),
('Banco Estado'),
('Banco BCI'),
('Banco Itaú'),
('Banco Scotiabank'),
('Banco Security'),
('Banco BBVA'),
('Banco Falabella'),
('Banco Ripley');

CREATE TABLE IF NOT EXISTS `Ciudad` (
  `id`     INT         NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
);
INSERT INTO `Ciudad` (nombre) VALUES ('San Antonio');

CREATE TABLE IF NOT EXISTS `Empresa` (
  `id`     INT          NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `url_pago` TEXT       NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `Empresa` (nombre) VALUES
('Chilquinta'),
('Esval'),
('Chilectra'),
('Aguas Andinas'),
('Enel');
-- ------------------------------------------------------------
-- CLIENTE Y TELÉFONO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Cliente` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(100) NOT NULL UNIQUE,
  `fecha_creacion`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rut`             VARCHAR(10)  NULL,
  `email`           VARCHAR(254) NULL,
  `ocupacion`       VARCHAR(100) NULL,
  `Nacionalidad_id` INT          NULL,
  `estado_civil`    ENUM('Soltero','Casado','Viudo','Divorciado') NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Nacionalidad_id`)   REFERENCES `Nacionalidad` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_rut_formato        CHECK (rut REGEXP '^[0-9]{7,8}-[0-9K]{1}$'),
  CONSTRAINT chk_email_formato      CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
);

INSERT INTO Cliente (id, nombre) VALUES (1, 'Corredor');

CREATE TABLE IF NOT EXISTS `Telefono` (
  `numero` VARCHAR(8) NOT NULL,
  `codigo` CHAR(1)    NOT NULL,
  `uso`    ENUM('Llamadas y Whatsapp','Solo Llamadas','Solo Whatsapp') NOT NULL,
  PRIMARY KEY (`numero`),
  CONSTRAINT chk_numero_telefono_formato  CHECK (numero REGEXP '^[0-9]{8}$'),
  CONSTRAINT chk_codigo_telefono_formato  CHECK (codigo IN ('2','9'))
);

CREATE TABLE IF NOT EXISTS `Telefono_Cliente` (
  `Telefono_id` VARCHAR(8)  NOT NULL,
  `Cliente_id`  INT NOT NULL,
  PRIMARY KEY (`Telefono_id`, `Cliente_id`),
  FOREIGN KEY (`Telefono_id`) REFERENCES `Telefono` (`numero`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`)     ON DELETE CASCADE ON UPDATE CASCADE
);
-- ------------------------------------------------------------
-- CUENTA BANCARIA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Cuenta_Bancaria` (
  `id`            INT         NOT NULL AUTO_INCREMENT,
  `numero_cuenta` VARCHAR(45) NOT NULL,
  `Cliente_id`   INT  NOT NULL,
  `Banco_id`      INT         NOT NULL,
  `tipo`          ENUM('Vista','Ahorro','Corriente') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`numero_cuenta`, `Banco_id`),
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Banco_id`)    REFERENCES `Banco` (`id`)    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_numero_cuenta_formato  CHECK (numero_cuenta REGEXP '^[0-9]{6,20}$')
);
-- ------------------------------------------------------------
-- PROPIEDAD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Propiedad` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `direccion`   VARCHAR(150)  NOT NULL,
  `propietario` INT   NOT NULL,
  FOREIGN KEY (`propietario`) REFERENCES `Cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  PRIMARY KEY (`id`) 
);

CREATE TABLE IF NOT EXISTS `Unidad` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100) NULL,
  `Propiedad_id` INT          NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Propiedad_id`) REFERENCES `Propiedad` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE (`nombre`, `Propiedad_id`)
);
-- ------------------------------------------------------------
-- CONTRATO Y CLÁUSULAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Contrato` (
  `id`                INT      NOT NULL AUTO_INCREMENT,
  `Unidad_id`         INT      NOT NULL,
  `administracion`    BOOLEAN  NOT NULL DEFAULT TRUE,
  -- Cobros generados al añadir participantes al contrato
  `comision_inicial`  INT      NULL,
  `garantia`          INT      NULL,
  -- Datos de Administracion
  `renta`             INT      NULL,
  `dia_pago`          TINYINT  NULL,
  `comision_mensual`  INT      NULL,
  -- Datos de Generación de contrato
  `fecha_firma`       DATETIME NULL,
  `fecha_inicio`      DATETIME NULL,
  `fecha_termino`     DATETIME NULL,
  `url_pdf`           TEXT     NULL,
  `Ciudad_id`         INT      NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Unidad_id`) REFERENCES `Unidad` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Ciudad_id`) REFERENCES `Ciudad` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_renta_contrato             CHECK (renta IS NULL OR renta> 0),
  CONSTRAINT chk_dia_pago_contrato          CHECK (dia_pago IS NULL OR dia_pago BETWEEN 1 AND 31),
  CONSTRAINT chk_fecha_inicio_contrato      CHECK (fecha_inicio IS NULL OR fecha_inicio >= fecha_firma),
  CONSTRAINT chk_fecha_termino_contrato     CHECK (fecha_termino IS NULL OR fecha_termino > fecha_inicio),
  CONSTRAINT chk_comision_inicial_contrato  CHECK (comision_inicial IS NULL OR comision_inicial > 0),
  CONSTRAINT chk_comision_mensual_contrato  CHECK (
    comision_mensual IS NULL OR (comision_mensual > 0 AND comision_mensual < renta)),
  -- Si hay administracion, tiene que haber renta y dia de pago
  CONSTRAINT chk_datos_administracion
    CHECK (
      (administracion = TRUE  AND (renta IS NOT NULL AND dia_pago IS NOT NULL))
      OR
      (administracion = FALSE AND renta IS NULL AND dia_pago IS NULL AND comision_mensual IS NULL)
    )
);

CREATE TABLE IF NOT EXISTS `Participante_Contrato` (
  `id`          INT AUTO_INCREMENT,
  `Cliente_id`  INT NOT NULL,
  `Contrato_id` INT NOT NULL,
  `rol`  ENUM('Arrendatario', 'Arrendador', 'Corredor','Co-arrendatario', 'Co-arrendador') NOT NULL,
  `monto`       INT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Cliente_id`)  REFERENCES `Cliente` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Contrato_id`) REFERENCES `Contrato` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `Clausula` (
  `id`        INT          NOT NULL AUTO_INCREMENT,
  `titulo`    VARCHAR(250) NOT NULL UNIQUE,
  `contenido` TEXT         NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `Campo_Clausula` (
  `id`          INT         NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(45) NOT NULL,
  `Clausula_id` INT         NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Clausula_id`) REFERENCES `Clausula` (`id`)
);

CREATE TABLE IF NOT EXISTS `Clausula_Contrato` (
  `Contrato_id` INT NOT NULL,
  `Clausula_id` INT NOT NULL,
  PRIMARY KEY (`Contrato_id`, `Clausula_id`),
  FOREIGN KEY (`Contrato_id`) REFERENCES `Contrato` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Clausula_id`) REFERENCES `Clausula` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `Valor_Campo_Clausula` (
  `Contrato_id`      INT          NOT NULL,
  `Campo_Clausula_id` INT         NOT NULL,
  `valor`            VARCHAR(255) NOT NULL,
  PRIMARY KEY (`Contrato_id`, `Campo_Clausula_id`),
  FOREIGN KEY (`Contrato_id`) REFERENCES `Contrato` (`id`),
  FOREIGN KEY (`Campo_Clausula_id`) REFERENCES `Campo_Clausula` (`id`)
);
-- ------------------------------------------------------------
-- SERVICIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Servicio` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `tipo` ENUM ('Luz', 'Agua', 'Gas', 'Gastos Comunes') NOT NULL,
  `dia_pago`        TINYINT       NOT NULL,
  `Propiedad_id`    INT           NOT NULL,
  `estado`          ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `numero_cliente`  VARCHAR(45)   NULL,
  `Empresa_id`      INT           NULL,
  `monto_fijo`      INT           NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Propiedad_id`)       REFERENCES `Propiedad` (`id`)       ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Empresa_id`)         REFERENCES `Empresa` (`id`)         ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_dia_pago_servicio       CHECK (dia_pago BETWEEN 1 AND 31),
  CONSTRAINT chk_numero_cliente_servicio CHECK (numero_cliente IS NULL OR numero_cliente REGEXP '^[A-Za-z0-9-]{5,20}$'),
  CONSTRAINT chk_monto_servicio          CHECK (monto_fijo IS NULL OR monto_fijo > 0)
);
-- ------------------------------------------------------------
-- TRANSACCIONES Y ORIGEN / DESTINO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Destino_Transaccion` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `tipo`               ENUM('Cuenta Bancaria','Empresa de Servicio') NOT NULL,
  `Servicio_id`        INT NULL,
  `Cliente_id`         INT NULL,
  `Cuenta_Bancaria_id` INT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Cuenta_Bancaria_id`) REFERENCES `Cuenta_Bancaria` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`Servicio_id`) REFERENCES `Servicio` (`id`)               ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`)                 ON DELETE SET NULL ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS `Origen_Transaccion` (
  `id`                 INT        NOT NULL AUTO_INCREMENT,
  `tipo`               ENUM('Cuenta Bancaria','Saldo del Cliente') NOT NULL DEFAULT 'Cuenta Bancaria',
  `Cliente_id`         INT NOT NULL,
  `Cuenta_Bancaria_id` INT        NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Cuenta_Bancaria_id`) REFERENCES `Cuenta_Bancaria` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`)                 ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `Transaccion` (
  `id`                     INT      NOT NULL AUTO_INCREMENT,
  `monto`                  INT      NOT NULL,
  `fecha`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Destino_Transaccion_id` INT      NOT NULL,
  `Origen_Transaccion_id`  INT      NOT NULL,
  `url_comprobante`        TEXT     NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Destino_Transaccion_id`) REFERENCES `Destino_Transaccion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Origen_Transaccion_id`)  REFERENCES `Origen_Transaccion` (`id`)   ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_transaccion_monto       CHECK (monto > 0)
);
-- ------------------------------------------------------------
-- COBRO Y PARTICIPANTES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Cobro` (
  `id`          INT      NOT NULL AUTO_INCREMENT,
  `fecha_cobro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado`      ENUM('Pagado','Incompleto','Pendiente','Vencido','Anulado') NOT NULL DEFAULT 'Pendiente',
   `tipo`        ENUM(
                  'Ingreso Renta Arrendatario',
                  'Egreso Renta Arrendador',
                  'Ingreso Proporcional Renta Arrendatario',
                  'Egreso Proporcional Renta Arrendador',
                  'Comision inicial arrendador',
                  'Comision inicial arrendatario',
                  'Comision Mensual',
                  'Ingreso Garantía Arrendatario',
                  'Egreso Garantía Arrendador',
                  'Devolución Garantía Arrendatario',
                  'Aseo Final',
                  'Luz',
                  'Agua',
                  'Gas',
                  'Gastos comunes',
                  'Reparación',
                  'Extra',
                  'Devolución'
                ) NOT NULL,
  `monto`       INT      NULL,
  `detalle`     TEXT     NULL, -- Para reparaciones y extras
  `Contrato_id` INT NULL,
  `Servicio_id` INT NULL,
  `Propiedad_id` INT NULL, -- Cuando el servicio lo paga el dueño
  `Unidad_id` INT NULL, -- Reparaciones
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Contrato_id`) REFERENCES `Contrato` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Servicio_id`) REFERENCES `Servicio` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Propiedad_id`) REFERENCES `Propiedad` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Unidad_id`) REFERENCES `Unidad` (`id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_cobro_monto CHECK (monto IS NULL or monto >= 0)
);

CREATE TABLE IF NOT EXISTS `Participante_Cobro` (
  `Cliente_id`  INT         NOT NULL,
  `Cobro_id`    INT         NOT NULL,
  `monto`       INT         NULL,
  `rol`         ENUM('Deudor','Acreedor') NOT NULL,
  PRIMARY KEY (`Cliente_id`, `Cobro_id`),
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Cobro_id`) REFERENCES `Cobro` (`id`)       ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_participante_cobro_monto    CHECK (monto IS NULL or monto  >= 0)
);

CREATE TABLE IF NOT EXISTS `Transaccion_Cobro` (
  `Transaccion_id` INT NOT NULL,
  `Cobro_id`       INT NOT NULL,
  `monto_pagado`   INT NOT NULL,
  PRIMARY KEY (`Transaccion_id`, `Cobro_id`),
  FOREIGN KEY (`Transaccion_id`) REFERENCES `Transaccion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Cobro_id`) REFERENCES `Cobro` (`id`)             ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_transaccion_cobro_monto CHECK (monto_pagado > 0)
);
-- ------------------------------------------------------------
-- SALDO CLIENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Saldo_Cliente` (
  `Transaccion_id` INT         NOT NULL,
  `Cliente_id`      INT        NOT NULL,
  `monto`          INT         NOT NULL,
  PRIMARY KEY (`Transaccion_id`),
  FOREIGN KEY (`Cliente_id`) REFERENCES `Cliente` (`id`)       ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Transaccion_id`) REFERENCES `Transaccion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_saldo_cliente_monto CHECK (monto > 0)
);

CREATE TABLE IF NOT EXISTS `Aplicacion_Saldo_Cobro` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `Saldo_Cliente_id` INT NOT NULL,
  `Cobro_id`         INT NOT NULL,
  `monto`            INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`Saldo_Cliente_id`, `Cobro_id`),
  FOREIGN KEY (`Saldo_Cliente_id`) REFERENCES `Saldo_Cliente` (`Transaccion_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`Cobro_id`) REFERENCES `Cobro` (`id`)                             ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_aplicacion_saldo_cobro_monto     CHECK (monto > 0)
);
-- ============================================================
-- PROCEDIMIENTO: AGREGAR ADMINISTRACION
-- ============================================================
DELIMITER $$
CREATE PROCEDURE sp_crear_contrato(
  IN p_arrendador VARCHAR(100),
  IN p_arrendatario VARCHAR(100),
  IN p_direccion VARCHAR(150),
  IN p_administracion BOOLEAN,
  IN p_renta INT,
  IN p_dia_pago TINYINT,
  IN p_comision_inicial INT,
  IN p_cobro_arrendador BOOLEAN,
  IN p_cobro_arrendatario BOOLEAN,
  IN p_dia_luz TINYINT,
  IN p_monto_luz INT,
  IN p_dia_agua TINYINT,
  IN p_monto_agua INT,
  IN p_dia_gas TINYINT,
  IN p_monto_gas INT,
  IN p_dia_gastos INT,
  IN p_monto_gastos INT,
  IN p_garantia INT,
  IN p_comision_mensual INT
)
BEGIN
  DECLARE v_propiedad_id INT;
  DECLARE v_unidad_id INT;
  DECLARE v_arrendador_id INT;
  DECLARE v_arrendatario_id INT;
  DECLARE v_contrato_id INT;
  DECLARE v_cobro_id INT;

  -- ARRENDADOR Y ARRENDATARIO
  SELECT id INTO v_arrendador_id FROM Cliente WHERE nombre = p_arrendador LIMIT 1;
  IF v_arrendador_id IS NULL THEN
    INSERT INTO Cliente(nombre) VALUES (p_arrendador);
    SET v_arrendador_id = LAST_INSERT_ID();
  END IF;

  SELECT id INTO v_arrendatario_id FROM Cliente WHERE nombre = p_arrendatario LIMIT 1;
  IF v_arrendatario_id IS NULL THEN
    INSERT INTO Cliente(nombre) VALUES (p_arrendatario);
    SET v_arrendatario_id = LAST_INSERT_ID();
  END IF;

  -- PROPIEDAD
  SELECT id INTO v_propiedad_id FROM Propiedad WHERE direccion = p_direccion LIMIT 1;
  IF v_propiedad_id IS NULL THEN
    INSERT INTO Propiedad(direccion, propietario) VALUES (p_direccion, v_arrendador_id);
    SET v_propiedad_id = LAST_INSERT_ID();
  END IF;

  -- UNIDAD (default única)
  SELECT id INTO v_unidad_id FROM Unidad WHERE Propiedad_id = v_propiedad_id LIMIT 1;
  IF v_unidad_id IS NULL THEN
    INSERT INTO Unidad(nombre, Propiedad_id) VALUES ('Unidad principal', v_propiedad_id);
    SET v_unidad_id = LAST_INSERT_ID();
  END IF;

  -- CONTRATO
  INSERT INTO Contrato(Unidad_id, administracion, comision_inicial, garantia, renta, dia_pago, comision_mensual) 
  VALUES (v_unidad_id, p_administracion, p_comision_inicial, p_garantia, p_renta, p_dia_pago, p_comision_mensual);
  SET v_contrato_id = LAST_INSERT_ID();

  -- PARTICIPANTES
  INSERT INTO Participante_Contrato (Cliente_id, Contrato_id, rol)
  VALUES
    (v_arrendador_id, v_contrato_id, 'Arrendador'),
    (v_arrendatario_id, v_contrato_id, 'Arrendatario'),
    (1, v_contrato_id, 'Corredor');

  -- COBRO COMISIÓN INICIAL
  IF p_comision_inicial IS NOT NULL AND v_arrendador_id <> 1 THEN
    IF p_cobro_arrendador = TRUE THEN
      INSERT INTO Cobro(tipo, monto, Contrato_id) VALUES ('Comision inicial arrendador', p_comision_inicial, v_contrato_id);
      SET v_cobro_id = LAST_INSERT_ID();
      INSERT INTO Participante_Cobro VALUES (v_arrendador_id, v_cobro_id, p_comision_inicial, 'Deudor');
      INSERT INTO Participante_Cobro VALUES (1, v_cobro_id, p_comision_inicial, 'Acreedor');
    END IF;
    IF p_cobro_arrendatario = TRUE THEN
      INSERT INTO Cobro(tipo, monto, Contrato_id) VALUES ('Comision inicial arrendatario', p_comision_inicial, v_contrato_id);
      SET v_cobro_id = LAST_INSERT_ID();
      INSERT INTO Participante_Cobro VALUES (v_arrendatario_id, v_cobro_id, p_comision_inicial, 'Deudor');
      INSERT INTO Participante_Cobro VALUES (1, v_cobro_id, p_comision_inicial, 'Acreedor');
    END IF;
  END IF;

  IF p_administracion THEN
    -- INGRESO RENTA ARRENDATARIO
    INSERT INTO Cobro (tipo, monto, Contrato_id) VALUES ('Ingreso Renta Arrendatario', p_renta, v_contrato_id);
    SET v_cobro_id = LAST_INSERT_ID();
    INSERT INTO Participante_Cobro VALUES (v_arrendatario_id, v_cobro_id, p_renta, 'Deudor');
    INSERT INTO Participante_Cobro VALUES (1, v_cobro_id, p_renta, 'Acreedor');

    -- EGRESO RENTA ARRENDATARIO
    IF  v_arrendador_id <> 1 THEN
      INSERT INTO Cobro (tipo, monto, Contrato_id) VALUES ('Egreso Renta Arrendador', p_renta, v_contrato_id);
      SET v_cobro_id = LAST_INSERT_ID();
      INSERT INTO Participante_Cobro VALUES (1, v_cobro_id, p_renta, 'Deudor');
      INSERT INTO Participante_Cobro VALUES (v_arrendador_id, v_cobro_id, p_renta, 'Acreedor');
    END IF;

    -- GARANTIA
    IF p_garantia IS NOT NULL THEN
      INSERT INTO Cobro(tipo, monto, Contrato_id) VALUES ('Ingreso Garantía Arrendatario', p_garantia, v_contrato_id);
      SET v_cobro_id = LAST_INSERT_ID();
      INSERT INTO Participante_Cobro VALUES (v_arrendatario_id, v_cobro_id, p_garantia, 'Deudor');
      INSERT INTO Participante_Cobro VALUES (1,   v_cobro_id, p_garantia, 'Acreedor');
      IF v_arrendador_id <> 1 THEN
        INSERT INTO Cobro(tipo, monto, Contrato_id) VALUES ('Egreso Garantía Arrendador', p_garantia, v_contrato_id);
        SET v_cobro_id = LAST_INSERT_ID();
        INSERT INTO Participante_Cobro VALUES (1, v_cobro_id, p_garantia, 'Deudor');
        INSERT INTO Participante_Cobro VALUES (v_arrendador_id,   v_cobro_id, p_garantia, 'Acreedor');
      END IF; 
    END IF;

     -- SERVICIOS
    IF p_dia_luz IS NOT NULL THEN
      INSERT INTO Servicio(tipo, dia_pago, Propiedad_id, monto_fijo) VALUES ('Luz', p_dia_luz, v_propiedad_id, p_monto_luz);
    END IF;
    IF p_dia_agua IS NOT NULL THEN
      INSERT INTO Servicio(tipo, dia_pago, Propiedad_id, monto_fijo) VALUES ('Agua', p_dia_agua, v_propiedad_id, p_monto_agua);
    END IF;
    IF p_dia_gas IS NOT NULL THEN
      INSERT INTO Servicio(tipo, dia_pago, Propiedad_id, monto_fijo) VALUES ('Gas', p_dia_gas, v_propiedad_id, p_monto_gas);
    END IF;
    IF p_dia_gastos IS NOT NULL THEN
      INSERT INTO Servicio(tipo, dia_pago, Propiedad_id, monto_fijo) VALUES ('Gastos Comunes', p_dia_gastos, v_propiedad_id, p_monto_gastos);
    END IF;
  END IF;    
END$$
DELIMITER ;
-- ============================================================
-- TRIGGER: ACTUALIZAR ESTADO DEL COBRO Y AGREGAR SALDO TRAS UNA TRANSACCION
-- ============================================================
DELIMITER $$
CREATE TRIGGER trg_post_insert_transaccion_cobro
AFTER INSERT ON Transaccion_Cobro
FOR EACH ROW
BEGIN
  DECLARE v_monto_cobro        INT;
  DECLARE v_estado_actual      VARCHAR(20);
  DECLARE v_total_pagado_cobro INT;
  DECLARE v_monto_transaccion  INT;
  DECLARE v_total_aplicado_tx  INT;
  DECLARE v_saldo_tx           INT;
  DECLARE v_cliente            INT;

  -- 1. ACTUALIZAR ESTADO DEL COBRO
  -- Obtener monto y estado del cobro
  SELECT monto, estado
  INTO v_monto_cobro, v_estado_actual
  FROM Cobro
  WHERE id = NEW.Cobro_id;

  -- Total pagado al cobro (incluye este insert)
  SELECT COALESCE(SUM(monto_pagado), 0)
  INTO v_total_pagado_cobro
  FROM Transaccion_Cobro
  WHERE Cobro_id = NEW.Cobro_id;

  -- Actualizar estado del cobro
  IF v_total_pagado_cobro >= v_monto_cobro THEN
    UPDATE Cobro SET estado = 'Pagado'
    WHERE id = NEW.Cobro_id;

  ELSEIF v_total_pagado_cobro > 0 THEN
    UPDATE Cobro SET estado = 'Incompleto'
    WHERE id = NEW.Cobro_id;

  ELSE
    UPDATE Cobro SET estado = 'Pendiente'
    WHERE id = NEW.Cobro_id;
  END IF;

  -- 2. CALCULAR SALDO SOBRANTE DE LA TRANSACCIÓN
  -- Monto total de la transacción
  SELECT monto
  INTO v_monto_transaccion
  FROM Transaccion
  WHERE id = NEW.Transaccion_id;

  -- Total ya aplicado por esta transacción (incluye este insert)
  SELECT COALESCE(SUM(monto_pagado), 0)
  INTO v_total_aplicado_tx
  FROM Transaccion_Cobro
  WHERE Transaccion_id = NEW.Transaccion_id;

  -- Saldo sobrante de la transacción
  SET v_saldo_tx = v_monto_transaccion - v_total_aplicado_tx;

  -- Obtener cliente origen de la transacción
  SELECT
    CASE
      WHEN ot.tipo = 'Saldo del Cliente' THEN ot.Cliente_id
      WHEN ot.tipo = 'Cuenta Bancaria'   THEN cb.Cliente_id
    END
  INTO v_cliente
  FROM Transaccion t
  JOIN Origen_Transaccion ot ON t.Origen_Transaccion_id = ot.id
  LEFT JOIN Cuenta_Bancaria cb ON cb.id = ot.Cuenta_Bancaria_id
  WHERE t.id = NEW.Transaccion_id;

  -- 3. GENERAR / ELIMINAR SALDO DEL CLIENTE
  IF v_cliente IS NOT NULL THEN
    IF v_saldo_tx > 0 THEN
      INSERT INTO Saldo_Cliente (Transaccion_id, Cliente_id, monto)
      VALUES (NEW.Transaccion_id, v_cliente, v_saldo_tx)
      ON DUPLICATE KEY UPDATE monto = v_saldo_tx;
    ELSE
      DELETE FROM Saldo_Cliente
      WHERE Transaccion_id = NEW.Transaccion_id;
    END IF;
  END IF;
END$$
DELIMITER ;
