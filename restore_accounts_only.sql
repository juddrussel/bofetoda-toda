-- ============================================================================
-- BOFETODA: Restore Accounts After Reset
-- ============================================================================
-- This script re-inserts officer and driver accounts after database reset
-- Run this AFTER you've reset the database contents
-- ============================================================================

USE bofetoda;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. INSERT OFFICERS (Admin Accounts)
-- ============================================================================
INSERT INTO officers (officer_id, name, initials, password, role, is_active) VALUES
('OFF-20240001', 'Avelino Conception', 'AC', 'president123', 'president', 1),
('OFF-20240002', 'Arturo Ventura', 'AV', 'secretary123', 'secretary', 1);

-- ============================================================================
-- 2. INSERT ALL 130 DRIVERS
-- ============================================================================
INSERT INTO drivers (driver_id, name, age, birthday, contact_no, address, barangay_id, body_no, color_id, renewal_status, password) VALUES
-- Antipona drivers (DRV-NT prefix)
('DRV-NT0072', 'Bienvenido Roxas', 28, '1999-02-27', '09815908301', '112 Aguinaldo Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0072', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'bienvenido64'),
('DRV-NT0297', 'Ricardo Medina', 32, '1995-04-21', '09909960308', '220 Bonifacio Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0297', (SELECT color_id FROM colors WHERE name='Green'), '-', 'ricardo25'),
('DRV-NT0211', 'Estelita Rivera', 60, '1967-11-06', '09199351819', '16 Quezon Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0211', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'estelita64'),
('DRV-NT0747', 'Alexis Ramirez', 62, '1965-08-12', '09432319487', '225 Luna Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0747', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'alexis19'),
('DRV-NT0359', 'Rolando Morales', 51, '1976-03-25', '09527601895', '88 Magsaysay Avenue, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0359', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'rolando68'),
('DRV-NT0364', 'Roberto Garcia', 42, '1985-08-23', '09104974650', '241 Del Pilar Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0364', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'roberto73'),
('DRV-NT0141', 'Ricardo Del Rosario', 43, '1984-03-24', '09366712768', '72 Maligaya Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0141', (SELECT color_id FROM colors WHERE name='Green'), '-', 'ricardo45'),
('DRV-NT0005', 'Marcelo Manalo', 49, '1978-04-05', '09122330792', '68 Malvar Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0005', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'marcelo57'),

-- Bagumbayan drivers (DRV-BG prefix)
('DRV-BG0064', 'Renato Estrada', 57, '1970-10-21', '09078666617', '163 Aguinaldo Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0064', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'renato36'),
('DRV-BG0896', 'Quirino Villanueva', 32, '1995-06-20', '09010928159', '7 Mabini Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0896', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'quirino29'),
('DRV-BG0351', 'Elena Manalo', 63, '1964-06-16', '09117777412', '27 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0351', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'elena98'),
('DRV-BG0172', 'Maria Jimenez', 26, '2001-04-17', '09528084148', '94 Maligaya Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0172', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'maria78'),
('DRV-BG0030', 'Amado Lacson', 57, '1970-06-21', '09393363387', '92 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0030', (SELECT color_id FROM colors WHERE name='Red'), '-', 'amado70'),
('DRV-BG0640', 'Milagros Aquino', 63, '1964-06-15', '09551313735', '53 Del Pilar Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0640', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'milagros71'),
('DRV-BG0970', 'Cesar Cabrera', 30, '1997-11-04', '09637265167', '103 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0970', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'cesar31'),
('DRV-BG0819', 'Renato Reyes', 34, '1993-10-15', '09299752882', '6 Rizal Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0819', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'renato27'),

-- Bambang drivers (DRV-BM prefix)
('DRV-BM0758', 'Erlinda Del Rosario', 26, '2001-05-07', '09483954862', '16 Maligaya Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0758', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'erlinda94'),
('DRV-BM0154', 'Filomena Jimenez', 51, '1976-09-05', '09828807290', '199 Sto. Nino Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0154', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'filomena89'),
('DRV-BM0791', 'Rolando Guerrero', 28, '1999-06-22', '09888718033', '71 Rizal Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0791', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'rolando81'),
('DRV-BM0520', 'Antonio Katigbak', 29, '1998-08-11', '09989834788', '207 Del Pilar Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0520', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'antonio43'),
('DRV-BM0803', 'Conrado Aquino', 53, '1974-03-14', '09167513613', '172 Malvar Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0803', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'conrado29'),
('DRV-BM0432', 'Emilio Castro', 41, '1986-03-15', '09316723268', '104 Luna Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0432', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'emilio21'),
('DRV-BM0808', 'Emilio Reyes', 46, '1981-09-15', '09706589481', '29 Maligaya Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0808', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'emilio23'),
('DRV-BM0718', 'Alfredo Salazar', 42, '1985-01-25', '09242646288', '147 Del Pilar Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0718', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'alfredo17'),
('DRV-BM0567', 'Corazon Lopez', 29, '1998-05-01', '09141931417', '3 Luna Street, Bambang, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bambang'), '0567', (SELECT color_id FROM colors WHERE name='Green'), '-', 'corazon44'),

-- Batia drivers (DRV-BT prefix)
('DRV-BT0689', 'Ramon Jimenez', 40, '1987-02-06', '09402344834', '115 Burgos Street, Batia, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Batia'), '0689', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'ramon12'),
('DRV-BT0560', 'Elena Cruz', 25, '2002-01-24', '09883873716', '169 Del Pilar Street, Batia, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Batia'), '0560', (SELECT color_id FROM colors WHERE name='Green'), '-', 'elena49'),
('DRV-BT0087', 'Cristina Aguilar', 46, '1981-04-27', '09265020146', '42 Rizal Street, Batia, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Batia'), '0087', (SELECT color_id FROM colors WHERE name='Green'), '-', 'cristina95'),
('DRV-BT0996', 'Norma Nunez', 40, '1987-12-10', '09072247045', '247 Luna Street, Batia, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Batia'), '0996', (SELECT color_id FROM colors WHERE name='White'), '-', 'norma14'),
('DRV-BT0094', 'Adoracion Del Rosario', 47, '1980-03-01', '09561748338', '199 Rizal Street, Batia, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Batia'), '0094', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'adoracion28'),

-- Biñang 1st drivers (DRV-BN prefix - note: shared with Biñang 2nd, Bundukan, Bunlo)
('DRV-BN0291', 'Godofredo Cruz', 50, '1977-01-10', '09431982965', '185 Del Pilar Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0291', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'godofredo15'),
('DRV-BN0137', 'Dionisio Batista', 52, '1975-12-23', '09828890931', '8 Rizal Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0137', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'dionisio58'),
('DRV-BN0539', 'Severino Guerrero', 28, '1999-11-01', '09837407188', '24 Roxas Avenue, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0539', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'severino70'),
('DRV-BN0659', 'Elena Ocampo', 41, '1986-04-24', '09337761740', '158 Roxas Avenue, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0659', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'elena28'),
('DRV-BN0529', 'Marites Salazar', 44, '1983-10-19', '09207074137', '75 Magsaysay Avenue, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0529', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'marites69'),
('DRV-BN0093', 'Josefina Rivera', 30, '1997-08-01', '09471874633', '20 Quezon Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0093', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'josefina43'),
('DRV-BN0698', 'Emilio Mendoza', 63, '1964-11-17', '09415377602', '1 Del Pilar Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0698', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'emilio28'),
('DRV-BN0400', 'Gregorio Domingo', 32, '1995-06-01', '09556130445', '17 Aguinaldo Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0400', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'gregorio64'),
('DRV-BN0906', 'Angelina Bautista', 42, '1985-02-02', '09423468535', '201 Aguinaldo Street, Biñang 1st, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 1st'), '0906', (SELECT color_id FROM colors WHERE name='Red'), '-', 'angelina90'),

-- Biñang 2nd drivers (DRV-BN prefix - shared)
('DRV-BN0484', 'Bayani Guerrero', 38, '1989-12-03', '09067924708', '33 Bonifacio Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0484', (SELECT color_id FROM colors WHERE name='Green'), '-', 'bayani48'),
('DRV-BN0928', 'Elena Javier', 41, '1986-07-21', '09347861221', '54 Burgos Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0928', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'elena67'),
('DRV-BN0265', 'Marites Katigbak', 53, '1974-07-05', '09833125815', '62 Luna Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0265', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'marites12'),
('DRV-BN0516', 'Lorenzo Santiago', 51, '1976-12-17', '09364507495', '33 Roxas Avenue, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0516', (SELECT color_id FROM colors WHERE name='White'), '-', 'lorenzo37'),
('DRV-BN0075', 'Rodolfo Navarro', 40, '1987-07-13', '09764020679', '126 Rizal Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0075', (SELECT color_id FROM colors WHERE name='Green'), '-', 'rodolfo77'),
('DRV-BN0239', 'Vicente Flores', 40, '1987-02-08', '09228171800', '201 Bonifacio Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0239', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'vicente92'),
('DRV-BN0810', 'Carmen Mendoza', 65, '1962-05-17', '09611148936', '67 Sampaguita Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0810', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'carmen78'),
('DRV-BN0199', 'Carmen Ramirez', 42, '1985-06-21', '09378383064', '15 Rizal Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0199', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'carmen92'),
('DRV-BN0817', 'Marcelo Garcia', 41, '1986-04-22', '09653705656', '51 Rizal Street, Biñang 2nd, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Biñang 2nd'), '0817', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'marcelo74'),

-- Bolacan drivers (DRV-BL prefix)
('DRV-BL0918', 'Remedios Diaz', 37, '1990-05-25', '09337344197', '157 Bonifacio Street, Bolacan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bolacan'), '0918', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'remedios95'),
('DRV-BL0730', 'Ricardo Nunez', 34, '1993-07-02', '09309260026', '116 Maligaya Street, Bolacan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bolacan'), '0730', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'ricardo20'),
('DRV-BL0081', 'Rosario Pascual', 37, '1990-03-21', '09870465572', '28 Rizal Street, Bolacan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bolacan'), '0081', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'rosario63'),
('DRV-BL0756', 'Remedios Santiago', 47, '1980-05-27', '09610735873', '83 Luna Street, Bolacan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bolacan'), '0756', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'remedios62'),

-- Bundukan drivers (DRV-BN prefix - shared)
('DRV-BN0045', 'Julio Cruz', 49, '1978-01-15', '09104319554', '86 Quezon Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0045', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'julio98'),
('DRV-BN0835', 'Alexis Navarro', 44, '1983-01-24', '09910317764', '234 Aguinaldo Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0835', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'alexis73'),
('DRV-BN0402', 'Corazon Santos', 44, '1983-12-25', '09293557591', '132 Sampaguita Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0402', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'corazon18'),
('DRV-BN0727', 'Manuel Rodriguez', 60, '1967-09-11', '09261149131', '108 Del Pilar Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0727', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'manuel27'),
('DRV-BN0204', 'Marcelo Ramirez', 64, '1963-11-08', '09814449454', '189 Malvar Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0204', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'marcelo41'),
('DRV-BN0038', 'Perla Castro', 43, '1984-10-07', '09516438831', '168 Del Pilar Street, Bundukan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bundukan'), '0038', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'perla39'),

-- Bunlo drivers (DRV-BN prefix - shared)
('DRV-BN0267', 'Florencio Cruz', 43, '1984-04-04', '09039931582', '115 Quezon Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0267', (SELECT color_id FROM colors WHERE name='Red'), '-', 'florencio86'),
('DRV-BN0381', 'Cesar Del Rosario', 27, '2000-06-11', '09203409305', '105 Roxas Avenue, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0381', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'cesar19'),
('DRV-BN0278', 'Remedios Cruz', 56, '1971-09-16', '09161682812', '102 Magsaysay Avenue, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0278', (SELECT color_id FROM colors WHERE name='Green'), '-', 'remedios95'),
('DRV-BN0445', 'Adoracion Dela Cruz', 28, '1999-05-24', '09956605366', '53 Rizal Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0445', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'adoracion21'),
('DRV-BN0380', 'Julio Medina', 48, '1979-08-25', '09220082619', '160 Maligaya Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0380', (SELECT color_id FROM colors WHERE name='White'), '-', 'julio54'),
('DRV-BN0949', 'Norma Villanueva', 58, '1969-03-03', '09167342075', '14 Quezon Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0949', (SELECT color_id FROM colors WHERE name='Green'), '-', 'norma89'),
('DRV-BN0368', 'Maria Batista', 39, '1988-10-13', '09937293068', '41 Aguinaldo Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0368', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'maria34'),
('DRV-BN0436', 'Ramon Guerrero', 27, '2000-11-27', '09516978464', '150 Sampaguita Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0436', (SELECT color_id FROM colors WHERE name='Green'), '-', 'ramon67'),
('DRV-BN0069', 'Crisanto Flores', 36, '1991-01-01', '09977379727', '103 Mabini Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0069', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'crisanto56'),
('DRV-BN0669', 'Rodolfo Flores', 57, '1970-09-22', '09002158108', '230 Aguinaldo Street, Bunlo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bunlo'), '0669', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'rodolfo18'),

-- Caingin drivers (DRV-CN prefix)
('DRV-CN0836', 'Erlinda Mendoza', 56, '1971-05-26', '09231594259', '71 Maligaya Street, Caingin, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Caingin'), '0836', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'erlinda74'),
('DRV-CN0285', 'Zoilo Del Rosario', 62, '1965-05-20', '09835503262', '163 Maligaya Street, Caingin, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Caingin'), '0285', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'zoilo31'),
('DRV-CN0272', 'Milagros Ramos', 58, '1969-01-21', '09578891486', '189 Sto. Nino Street, Caingin, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Caingin'), '0272', (SELECT color_id FROM colors WHERE name='Green'), '-', 'milagros83'),
('DRV-CN0321', 'Arnel Ventura', 46, '1981-02-15', '09329048449', '238 Roxas Avenue, Caingin, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Caingin'), '0321', (SELECT color_id FROM colors WHERE name='Red'), '-', 'arnel38'),

-- Duhat drivers (DRV-DH prefix)
('DRV-DH0581', 'Lourdes Espino', 65, '1962-07-14', '09850273900', '14 Rizal Street, Duhat, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Duhat'), '0581', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'lourdes76'),
('DRV-DH0821', 'Domingo Vasquez', 39, '1988-07-19', '09492359722', '4 Maligaya Street, Duhat, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Duhat'), '0821', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'domingo67'),
('DRV-DH0960', 'Rogelio Ocampo', 65, '1962-03-28', '09464008599', '114 Quezon Street, Duhat, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Duhat'), '0960', (SELECT color_id FROM colors WHERE name='White'), '-', 'rogelio41'),
('DRV-DH0964', 'Rosario Santos', 27, '2000-01-18', '09062320109', '142 Roxas Avenue, Duhat, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Duhat'), '0964', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'rosario35'),
('DRV-DH0007', 'Efren Nunez', 57, '1970-11-21', '09692841407', '184 Burgos Street, Duhat, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Duhat'), '0007', (SELECT color_id FROM colors WHERE name='Green'), '-', 'efren69'),

-- Igulot drivers (DRV-GL prefix)
('DRV-GL0940', 'Severino Fernandez', 39, '1988-02-09', '09301540486', '176 Sto. Nino Street, Igulot, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Igulot'), '0940', (SELECT color_id FROM colors WHERE name='White'), '-', 'severino47'),
('DRV-GL0930', 'Cristina Garcia', 57, '1970-01-06', '09433253659', '62 Aguinaldo Street, Igulot, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Igulot'), '0930', (SELECT color_id FROM colors WHERE name='White'), '-', 'cristina77'),
('DRV-GL0028', 'Juan Reyes', 52, '1975-12-08', '09943699192', '38 Rizal Street, Igulot, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Igulot'), '0028', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'juan30'),
('DRV-GL0068', 'Cesar Castro', 26, '2001-01-02', '09201019538', '229 Roxas Avenue, Igulot, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Igulot'), '0068', (SELECT color_id FROM colors WHERE name='Green'), '-', 'cesar36'),

-- Lolomboy drivers (DRV-LL prefix)
('DRV-LL0022', 'Romeo Cruz', 27, '2000-11-03', '09471213455', '109 Malvar Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0022', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'romeo46'),
('DRV-LL0101', 'Eduardo Gutierrez', 48, '1979-06-25', '09987490606', '133 Sto. Nino Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0101', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'eduardo16'),
('DRV-LL0056', 'Herminio Medina', 38, '1989-12-28', '09194260834', '196 Sto. Nino Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0056', (SELECT color_id FROM colors WHERE name='Red'), '-', 'herminio22'),
('DRV-LL0962', 'Aurelio Estrada', 36, '1991-08-19', '09584924337', '43 Mabini Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0962', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'aurelio99'),
('DRV-LL0923', 'Conrado Torres', 65, '1962-06-12', '09166160534', '68 Aguinaldo Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0923', (SELECT color_id FROM colors WHERE name='White'), '-', 'conrado58'),
('DRV-LL0174', 'Marilou Ramirez', 33, '1994-09-20', '09905958278', '190 Luna Street, Lolomboy, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Lolomboy'), '0174', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'marilou42'),

-- Poblacion drivers (DRV-PB prefix)
('DRV-PB0618', 'Renato Pascual', 54, '1973-11-23', '09383449223', '186 Luna Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0618', (SELECT color_id FROM colors WHERE name='White'), '-', 'renato40'),
('DRV-PB0112', 'Ronaldo Aquino', 41, '1986-12-04', '09213622446', '71 Sampaguita Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0112', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'ronaldo59'),
('DRV-PB0006', 'Vicente Cruz', 25, '2002-07-28', '09638470249', '189 Aguinaldo Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0006', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'vicente65'),
('DRV-PB0249', 'Estelita Castillo', 51, '1976-04-22', '09932176541', '230 Aguinaldo Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0249', (SELECT color_id FROM colors WHERE name='Green'), '-', 'estelita90'),
('DRV-PB0040', 'Maria Salazar', 52, '1975-08-15', '09096825067', '233 Mabini Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0040', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'maria30'),
('DRV-PB0760', 'Josefina Jimenez', 47, '1980-02-28', '09978378058', '88 Aguinaldo Street, Poblacion, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Poblacion'), '0760', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'josefina97'),

-- Sulucan drivers (DRV-SL prefix)
('DRV-SL0431', 'Ignacio Morales', 32, '1995-12-20', '09504466001', '108 Maligaya Street, Sulucan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Sulucan'), '0431', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'ignacio23'),
('DRV-SL0987', 'Leonora Rivera', 50, '1977-09-08', '09673221378', '185 Sampaguita Street, Sulucan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Sulucan'), '0987', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'leonora91'),
('DRV-SL0825', 'Lorenzo Ramirez', 43, '1984-09-21', '09275346462', '124 Rizal Street, Sulucan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Sulucan'), '0825', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'lorenzo93'),
('DRV-SL0966', 'Carmen Domingo', 55, '1972-08-14', '09915246019', '232 Luna Street, Sulucan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Sulucan'), '0966', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'carmen54'),
('DRV-SL0157', 'Filomena Santos', 25, '2002-04-03', '09449192327', '89 Sto. Nino Street, Sulucan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Sulucan'), '0157', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'filomena78'),

-- Taal drivers (DRV-TL prefix)
('DRV-TL0271', 'Justina Estrada', 63, '1964-02-22', '09843738171', '143 Mabini Street, Taal, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Taal'), '0271', (SELECT color_id FROM colors WHERE name='Green'), '-', 'justina27'),
('DRV-TL0329', 'Wilfredo Diaz', 60, '1967-01-16', '09727372890', '42 San Jose Street, Taal, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Taal'), '0329', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'wilfredo73'),
('DRV-TL0497', 'Lourdes Ramirez', 48, '1979-07-14', '09125009051', '131 Del Pilar Street, Taal, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Taal'), '0497', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'lourdes63'),
('DRV-TL0300', 'Rogelio Corpuz', 48, '1979-06-16', '09883465648', '14 San Jose Street, Taal, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Taal'), '0300', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'rogelio61'),
('DRV-TL0090', 'Marites Morales', 42, '1985-09-12', '09371535429', '250 Roxas Avenue, Taal, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Taal'), '0090', (SELECT color_id FROM colors WHERE name='Red'), '-', 'marites80'),

-- Tambobong drivers (DRV-TM prefix)
('DRV-TM0632', 'Amado Medina', 28, '1999-07-10', '09100379088', '157 Aguinaldo Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0632', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'amado99'),
('DRV-TM0143', 'Honorato De Guzman', 30, '1997-04-02', '09721206105', '224 San Jose Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0143', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'honorato43'),
('DRV-TM0793', 'Carmen Fernandez', 51, '1976-01-11', '09069907980', '212 Mabini Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0793', (SELECT color_id FROM colors WHERE name='Green'), '-', 'carmen61'),
('DRV-TM0156', 'Severino Ocampo', 25, '2002-11-13', '09992768117', '55 Maligaya Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0156', (SELECT color_id FROM colors WHERE name='Red'), '-', 'severino11'),
('DRV-TM0731', 'Rolando Garcia', 38, '1989-02-05', '09704937205', '199 Magsaysay Avenue, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0731', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'rolando20'),
('DRV-TM0320', 'Lourdes Batista', 60, '1967-12-16', '09740000091', '100 Malvar Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0320', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'lourdes72'),
('DRV-TM0806', 'Isabelo Bautista', 45, '1982-06-19', '09772215267', '99 Sto. Nino Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0806', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'isabelo82'),
('DRV-TM0253', 'Marites Gonzales', 42, '1985-01-20', '09959029496', '250 Maligaya Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0253', (SELECT color_id FROM colors WHERE name='Green'), '-', 'marites58'),
('DRV-TM0832', 'Isabelo Lacson', 39, '1988-08-10', '09054462904', '214 Bonifacio Street, Tambobong, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Tambobong'), '0832', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'isabelo80'),

-- Turo drivers (DRV-TR prefix)
('DRV-TR0261', 'Cesar Vasquez', 30, '1997-09-18', '09763349067', '182 Sampaguita Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0261', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'cesar59'),
('DRV-TR0604', 'Teodoro Vasquez', 30, '1997-09-26', '09513698485', '123 Burgos Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0604', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'teodoro34'),
('DRV-TR0648', 'Rodolfo Fernandez', 43, '1984-06-19', '09956823075', '222 Mabini Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0648', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'rodolfo29'),
('DRV-TR0268', 'Alexis Nunez', 26, '2001-06-09', '09890103979', '146 Sampaguita Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0268', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'alexis67'),
('DRV-TR0892', 'Godofredo Nunez', 33, '1994-05-27', '09053261000', '143 Luna Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0892', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'godofredo18'),
('DRV-TR0164', 'Honorato Batista', 50, '1977-02-23', '09145931862', '115 San Jose Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0164', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'honorato38'),
('DRV-TR0694', 'Teresita Cruz', 41, '1986-06-02', '09800487012', '82 Sto. Nino Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0694', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'teresita66'),
('DRV-TR0914', 'Danilo Rodriguez', 45, '1982-06-09', '09615762732', '235 Roxas Avenue, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0914', (SELECT color_id FROM colors WHERE name='Red'), '-', 'danilo34'),
('DRV-TR0240', 'Manuel Villanueva', 39, '1988-02-20', '09527160175', '83 San Jose Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0240', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'manuel56'),
('DRV-TR0027', 'Arnel Pascual', 39, '1988-12-02', '09278272466', '64 Bonifacio Street, Turo, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Turo'), '0027', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'arnel47'),

-- Wakas drivers (DRV-WK prefix)
('DRV-WK0123', 'Rosario Salazar', 56, '1971-02-11', '09771280387', '214 Malvar Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0123', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'rosario56'),
('DRV-WK0350', 'Pablo Salazar', 40, '1987-04-04', '09646204207', '207 Burgos Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0350', (SELECT color_id FROM colors WHERE name='White'), '-', 'pablo10'),
('DRV-WK0729', 'Gaudencio Gonzales', 36, '1991-06-14', '09063492228', '198 Sampaguita Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0729', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'gaudencio20'),
('DRV-WK0533', 'Rodolfo Nunez', 56, '1971-05-06', '09329394301', '178 Magsaysay Avenue, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0533', (SELECT color_id FROM colors WHERE name='Green'), '-', 'rodolfo17'),
('DRV-WK0376', 'Efren Manalo', 46, '1981-05-27', '09710672432', '145 San Jose Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0376', (SELECT color_id FROM colors WHERE name='Red'), '-', 'efren57'),
('DRV-WK0063', 'Estelita Nunez', 25, '2002-06-17', '09781153569', '193 Maligaya Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0063', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'estelita73'),
('DRV-WK0257', 'Severino Morales', 26, '2001-09-26', '09820313922', '27 Malvar Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0257', (SELECT color_id FROM colors WHERE name='White'), '-', 'severino13'),
('DRV-WK0047', 'Jose Torres', 37, '1990-05-01', '09997837151', '184 Bonifacio Street, Wakas, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Wakas'), '0047', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'jose73');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
SELECT 'Officers restored:' as status, COUNT(*) as count FROM officers;
SELECT 'Drivers restored:' as status, COUNT(*) as count FROM drivers;

-- Show sample of each
SELECT officer_id, name, role FROM officers;
SELECT driver_id, name, body_no FROM drivers LIMIT 10;

SELECT '✓ Account restoration completed!' as status;
