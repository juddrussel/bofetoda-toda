<?php
/**
 * Driver Seeding Script
 * Access: https://your-domain.com/seed_drivers.php
 * 
 * IMPORTANT: Delete this file after running!
 */

require_once __DIR__ . '/db.php';

$pdo = get_pdo();

// Start output
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Driver Seeding</title><style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style></head><body>';

echo '<h1>🌱 Driver Seeding Script</h1>';
echo '<p class="info">Starting driver insertion...</p>';

// Drivers data
$drivers = [
    ['DRV-0001', 'Bienvenido Roxas', 28, '1999-02-27', '09815908301', '112 Aguinaldo Street, Antipona, Bocaue, Bulacan', 'Antipona', '0072', 'Brown', '-', 'bienvenido64'],
    ['DRV-0002', 'Ricardo Medina', 32, '1995-04-21', '09909960308', '220 Bonifacio Street, Antipona, Bocaue, Bulacan', 'Antipona', '0297', 'Green', '-', 'ricardo25'],
    ['DRV-0003', 'Estelita Rivera', 60, '1967-11-06', '09199351819', '16 Quezon Street, Antipona, Bocaue, Bulacan', 'Antipona', '0211', 'Maroon', '-', 'estelita64'],
    ['DRV-0004', 'Alexis Ramirez', 62, '1965-08-12', '09432319487', '225 Luna Street, Antipona, Bocaue, Bulacan', 'Antipona', '0747', 'Maroon', '-', 'alexis19'],
    ['DRV-0005', 'Rolando Morales', 51, '1976-03-25', '09527601895', '88 Magsaysay Avenue, Antipona, Bocaue, Bulacan', 'Antipona', '0359', 'Purple', '-', 'rolando68'],
    ['DRV-0006', 'Roberto Garcia', 42, '1985-08-23', '09104974650', '241 Del Pilar Street, Antipona, Bocaue, Bulacan', 'Antipona', '0364', 'Blue', '-', 'roberto73'],
    ['DRV-0007', 'Ricardo Del Rosario', 43, '1984-03-24', '09366712768', '72 Maligaya Street, Antipona, Bocaue, Bulacan', 'Antipona', '0141', 'Green', '-', 'ricardo45'],
    ['DRV-0008', 'Marcelo Manalo', 49, '1978-04-05', '09122330792', '68 Malvar Street, Antipona, Bocaue, Bulacan', 'Antipona', '0005', 'Blue', '-', 'marcelo57'],
    ['DRV-0009', 'Renato Estrada', 57, '1970-10-21', '09078666617', '163 Aguinaldo Street, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0064', 'Brown', '-', 'renato36'],
    ['DRV-0010', 'Quirino Villanueva', 32, '1995-06-20', '09010928159', '7 Mabini Street, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0896', 'Brown', '-', 'quirino29'],
    ['DRV-0011', 'Elena Manalo', 63, '1964-06-16', '09117777412', '27 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0351', 'Pink', '-', 'elena98'],
    ['DRV-0012', 'Maria Jimenez', 26, '2001-04-17', '09528084148', '94 Maligaya Street, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0172', 'Orange', '-', 'maria78'],
    ['DRV-0013', 'Amado Lacson', 57, '1970-06-21', '09393363387', '92 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0030', 'Red', '-', 'amado70'],
    ['DRV-0014', 'Milagros Aquino', 63, '1964-06-15', '09551313735', '53 Del Pilar Street, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0640', 'Purple', '-', 'milagros71'],
    ['DRV-0015', 'Cesar Cabrera', 30, '1997-11-04', '09637265167', '103 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0970', 'Yellow', '-', 'cesar31'],
    ['DRV-0016', 'Renato Reyes', 34, '1993-10-15', '09299752882', '6 Rizal Street, Bagumbayan, Bocaue, Bulacan', 'Bagumbayan', '0819', 'Yellow', '-', 'renato27'],
    ['DRV-0017', 'Erlinda Del Rosario', 26, '2001-05-07', '09483954862', '16 Maligaya Street, Bambang, Bocaue, Bulacan', 'Bambang', '0758', 'Orange', '-', 'erlinda94'],
    ['DRV-0018', 'Filomena Jimenez', 51, '1976-09-05', '09828807290', '199 Sto. Nino Street, Bambang, Bocaue, Bulacan', 'Bambang', '0154', 'Blue', '-', 'filomena89'],
    ['DRV-0019', 'Rolando Guerrero', 28, '1999-06-22', '09888718033', '71 Rizal Street, Bambang, Bocaue, Bulacan', 'Bambang', '0791', 'Yellow', '-', 'rolando81'],
    ['DRV-0020', 'Antonio Katigbak', 29, '1998-08-11', '09989834788', '207 Del Pilar Street, Bambang, Bocaue, Bulacan', 'Bambang', '0520', 'Brown', '-', 'antonio43'],
    ['DRV-0021', 'Conrado Aquino', 53, '1974-03-14', '09167513613', '172 Malvar Street, Bambang, Bocaue, Bulacan', 'Bambang', '0803', 'Yellow', '-', 'conrado29'],
    ['DRV-0022', 'Emilio Castro', 41, '1986-03-15', '09316723268', '104 Luna Street, Bambang, Bocaue, Bulacan', 'Bambang', '0432', 'Brown', '-', 'emilio21'],
    ['DRV-0023', 'Emilio Reyes', 46, '1981-09-15', '09706589481', '29 Maligaya Street, Bambang, Bocaue, Bulacan', 'Bambang', '0808', 'Brown', '-', 'emilio23'],
    ['DRV-0024', 'Alfredo Salazar', 42, '1985-01-25', '09242646288', '147 Del Pilar Street, Bambang, Bocaue, Bulacan', 'Bambang', '0718', 'Orange', '-', 'alfredo17'],
    ['DRV-0025', 'Corazon Lopez', 29, '1998-05-01', '09141931417', '3 Luna Street, Bambang, Bocaue, Bulacan', 'Bambang', '0567', 'Green', '-', 'corazon44'],
    ['DRV-0026', 'Ramon Jimenez', 40, '1987-02-06', '09402344834', '115 Burgos Street, Batia, Bocaue, Bulacan', 'Batia', '0689', 'Blue', '-', 'ramon12'],
    ['DRV-0027', 'Elena Cruz', 25, '2002-01-24', '09883873716', '169 Del Pilar Street, Batia, Bocaue, Bulacan', 'Batia', '0560', 'Green', '-', 'elena49'],
    ['DRV-0028', 'Cristina Aguilar', 46, '1981-04-27', '09265020146', '42 Rizal Street, Batia, Bocaue, Bulacan', 'Batia', '0087', 'Green', '-', 'cristina95'],
    ['DRV-0029', 'Norma Nunez', 40, '1987-12-10', '09072247045', '247 Luna Street, Batia, Bocaue, Bulacan', 'Batia', '0996', 'White', '-', 'norma14'],
    ['DRV-0030', 'Adoracion Del Rosario', 47, '1980-03-01', '09561748338', '199 Rizal Street, Batia, Bocaue, Bulacan', 'Batia', '0094', 'Pink', '-', 'adoracion28'],
    ['DRV-0031', 'Godofredo Cruz', 50, '1977-01-10', '09431982965', '185 Del Pilar Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0291', 'Purple', '-', 'godofredo15'],
    ['DRV-0032', 'Dionisio Batista', 52, '1975-12-23', '09828890931', '8 Rizal Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0137', 'Orange', '-', 'dionisio58'],
    ['DRV-0033', 'Severino Guerrero', 28, '1999-11-01', '09837407188', '24 Roxas Avenue, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0539', 'Yellow', '-', 'severino70'],
    ['DRV-0034', 'Elena Ocampo', 41, '1986-04-24', '09337761740', '158 Roxas Avenue, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0659', 'Brown', '-', 'elena28'],
    ['DRV-0035', 'Marites Salazar', 44, '1983-10-19', '09207074137', '75 Magsaysay Avenue, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0529', 'Pink', '-', 'marites69'],
    ['DRV-0036', 'Josefina Rivera', 30, '1997-08-01', '09471874633', '20 Quezon Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0093', 'Blue', '-', 'josefina43'],
    ['DRV-0037', 'Emilio Mendoza', 63, '1964-11-17', '09415377602', '1 Del Pilar Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0698', 'Maroon', '-', 'emilio28'],
    ['DRV-0038', 'Gregorio Domingo', 32, '1995-06-01', '09556130445', '17 Aguinaldo Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0400', 'Purple', '-', 'gregorio64'],
    ['DRV-0039', 'Angelina Bautista', 42, '1985-02-02', '09423468535', '201 Aguinaldo Street, Biñang 1st, Bocaue, Bulacan', 'Biñang 1st', '0906', 'Red', '-', 'angelina90'],
    ['DRV-0040', 'Bayani Guerrero', 38, '1989-12-03', '09067924708', '33 Bonifacio Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0484', 'Green', '-', 'bayani48'],
    ['DRV-0041', 'Elena Javier', 41, '1986-07-21', '09347861221', '54 Burgos Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0928', 'Maroon', '-', 'elena67'],
    ['DRV-0042', 'Marites Katigbak', 53, '1974-07-05', '09833125815', '62 Luna Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0265', 'Purple', '-', 'marites12'],
    ['DRV-0043', 'Lorenzo Santiago', 51, '1976-12-17', '09364507495', '33 Roxas Avenue, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0516', 'White', '-', 'lorenzo37'],
    ['DRV-0044', 'Rodolfo Navarro', 40, '1987-07-13', '09764020679', '126 Rizal Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0075', 'Green', '-', 'rodolfo77'],
    ['DRV-0045', 'Vicente Flores', 40, '1987-02-08', '09228171800', '201 Bonifacio Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0239', 'Purple', '-', 'vicente92'],
    ['DRV-0046', 'Carmen Mendoza', 65, '1962-05-17', '09611148936', '67 Sampaguita Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0810', 'Purple', '-', 'carmen78'],
    ['DRV-0047', 'Carmen Ramirez', 42, '1985-06-21', '09378383064', '15 Rizal Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0199', 'Maroon', '-', 'carmen92'],
    ['DRV-0048', 'Marcelo Garcia', 41, '1986-04-22', '09653705656', '51 Rizal Street, Biñang 2nd, Bocaue, Bulacan', 'Biñang 2nd', '0817', 'Pink', '-', 'marcelo74'],
    ['DRV-0049', 'Remedios Diaz', 37, '1990-05-25', '09337344197', '157 Bonifacio Street, Bolacan, Bocaue, Bulacan', 'Bolacan', '0918', 'Brown', '-', 'remedios95'],
    ['DRV-0050', 'Ricardo Nunez', 34, '1993-07-02', '09309260026', '116 Maligaya Street, Bolacan, Bocaue, Bulacan', 'Bolacan', '0730', 'Orange', '-', 'ricardo20'],
    ['DRV-0051', 'Rosario Pascual', 37, '1990-03-21', '09870465572', '28 Rizal Street, Bolacan, Bocaue, Bulacan', 'Bolacan', '0081', 'Pink', '-', 'rosario63'],
    ['DRV-0052', 'Remedios Santiago', 47, '1980-05-27', '09610735873', '83 Luna Street, Bolacan, Bocaue, Bulacan', 'Bolacan', '0756', 'Maroon', '-', 'remedios62'],
    ['DRV-0053', 'Julio Cruz', 49, '1978-01-15', '09104319554', '86 Quezon Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0045', 'Pink', '-', 'julio98'],
    ['DRV-0054', 'Alexis Navarro', 44, '1983-01-24', '09910317764', '234 Aguinaldo Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0835', 'Maroon', '-', 'alexis73'],
    ['DRV-0055', 'Corazon Santos', 44, '1983-12-25', '09293557591', '132 Sampaguita Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0402', 'Blue', '-', 'corazon18'],
    ['DRV-0056', 'Manuel Rodriguez', 60, '1967-09-11', '09261149131', '108 Del Pilar Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0727', 'Maroon', '-', 'manuel27'],
    ['DRV-0057', 'Marcelo Ramirez', 64, '1963-11-08', '09814449454', '189 Malvar Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0204', 'Maroon', '-', 'marcelo41'],
    ['DRV-0058', 'Perla Castro', 43, '1984-10-07', '09516438831', '168 Del Pilar Street, Bundukan, Bocaue, Bulacan', 'Bundukan', '0038', 'Yellow', '-', 'perla39'],
    ['DRV-0059', 'Florencio Cruz', 43, '1984-04-04', '09039931582', '115 Quezon Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0267', 'Red', '-', 'florencio86'],
    ['DRV-0060', 'Cesar Del Rosario', 27, '2000-06-11', '09203409305', '105 Roxas Avenue, Bunlo, Bocaue, Bulacan', 'Bunlo', '0381', 'Blue', '-', 'cesar19'],
    ['DRV-0061', 'Remedios Cruz', 56, '1971-09-16', '09161682812', '102 Magsaysay Avenue, Bunlo, Bocaue, Bulacan', 'Bunlo', '0278', 'Green', '-', 'remedios95'],
    ['DRV-0062', 'Adoracion Dela Cruz', 28, '1999-05-24', '09956605366', '53 Rizal Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0445', 'Blue', '-', 'adoracion21'],
    ['DRV-0063', 'Julio Medina', 48, '1979-08-25', '09220082619', '160 Maligaya Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0380', 'White', '-', 'julio54'],
    ['DRV-0064', 'Norma Villanueva', 58, '1969-03-03', '09167342075', '14 Quezon Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0949', 'Green', '-', 'norma89'],
    ['DRV-0065', 'Maria Batista', 39, '1988-10-13', '09937293068', '41 Aguinaldo Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0368', 'Yellow', '-', 'maria34'],
    ['DRV-0066', 'Ramon Guerrero', 27, '2000-11-27', '09516978464', '150 Sampaguita Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0436', 'Green', '-', 'ramon67'],
    ['DRV-0067', 'Crisanto Flores', 36, '1991-01-01', '09977379727', '103 Mabini Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0069', 'Blue', '-', 'crisanto56'],
    ['DRV-0068', 'Rodolfo Flores', 57, '1970-09-22', '09002158108', '230 Aguinaldo Street, Bunlo, Bocaue, Bulacan', 'Bunlo', '0669', 'Blue', '-', 'rodolfo18'],
    ['DRV-0069', 'Erlinda Mendoza', 56, '1971-05-26', '09231594259', '71 Maligaya Street, Caingin, Bocaue, Bulacan', 'Caingin', '0836', 'Maroon', '-', 'erlinda74'],
    ['DRV-0070', 'Zoilo Del Rosario', 62, '1965-05-20', '09835503262', '163 Maligaya Street, Caingin, Bocaue, Bulacan', 'Caingin', '0285', 'Orange', '-', 'zoilo31'],
    ['DRV-0071', 'Milagros Ramos', 58, '1969-01-21', '09578891486', '189 Sto. Nino Street, Caingin, Bocaue, Bulacan', 'Caingin', '0272', 'Green', '-', 'milagros83'],
    ['DRV-0072', 'Arnel Ventura', 46, '1981-02-15', '09329048449', '238 Roxas Avenue, Caingin, Bocaue, Bulacan', 'Caingin', '0321', 'Red', '-', 'arnel38'],
    ['DRV-0073', 'Lourdes Espino', 65, '1962-07-14', '09850273900', '14 Rizal Street, Duhat, Bocaue, Bulacan', 'Duhat', '0581', 'Orange', '-', 'lourdes76'],
    ['DRV-0074', 'Domingo Vasquez', 39, '1988-07-19', '09492359722', '4 Maligaya Street, Duhat, Bocaue, Bulacan', 'Duhat', '0821', 'Brown', '-', 'domingo67'],
    ['DRV-0075', 'Rogelio Ocampo', 65, '1962-03-28', '09464008599', '114 Quezon Street, Duhat, Bocaue, Bulacan', 'Duhat', '0960', 'White', '-', 'rogelio41'],
    ['DRV-0076', 'Rosario Santos', 27, '2000-01-18', '09062320109', '142 Roxas Avenue, Duhat, Bocaue, Bulacan', 'Duhat', '0964', 'Brown', '-', 'rosario35'],
    ['DRV-0077', 'Efren Nunez', 57, '1970-11-21', '09692841407', '184 Burgos Street, Duhat, Bocaue, Bulacan', 'Duhat', '0007', 'Green', '-', 'efren69'],
    ['DRV-0078', 'Severino Fernandez', 39, '1988-02-09', '09301540486', '176 Sto. Nino Street, Igulot, Bocaue, Bulacan', 'Igulot', '0940', 'White', '-', 'severino47'],
    ['DRV-0079', 'Cristina Garcia', 57, '1970-01-06', '09433253659', '62 Aguinaldo Street, Igulot, Bocaue, Bulacan', 'Igulot', '0930', 'White', '-', 'cristina77'],
    ['DRV-0080', 'Juan Reyes', 52, '1975-12-08', '09943699192', '38 Rizal Street, Igulot, Bocaue, Bulacan', 'Igulot', '0028', 'Yellow', '-', 'juan30'],
    ['DRV-0081', 'Cesar Castro', 26, '2001-01-02', '09201019538', '229 Roxas Avenue, Igulot, Bocaue, Bulacan', 'Igulot', '0068', 'Green', '-', 'cesar36'],
    ['DRV-0082', 'Romeo Cruz', 27, '2000-11-03', '09471213455', '109 Malvar Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0022', 'Orange', '-', 'romeo46'],
    ['DRV-0083', 'Eduardo Gutierrez', 48, '1979-06-25', '09987490606', '133 Sto. Nino Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0101', 'Orange', '-', 'eduardo16'],
    ['DRV-0084', 'Herminio Medina', 38, '1989-12-28', '09194260834', '196 Sto. Nino Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0056', 'Red', '-', 'herminio22'],
    ['DRV-0085', 'Aurelio Estrada', 36, '1991-08-19', '09584924337', '43 Mabini Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0962', 'Yellow', '-', 'aurelio99'],
    ['DRV-0086', 'Conrado Torres', 65, '1962-06-12', '09166160534', '68 Aguinaldo Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0923', 'White', '-', 'conrado58'],
    ['DRV-0087', 'Marilou Ramirez', 33, '1994-09-20', '09905958278', '190 Luna Street, Lolomboy, Bocaue, Bulacan', 'Lolomboy', '0174', 'Maroon', '-', 'marilou42'],
    ['DRV-0088', 'Renato Pascual', 54, '1973-11-23', '09383449223', '186 Luna Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0618', 'White', '-', 'renato40'],
    ['DRV-0089', 'Ronaldo Aquino', 41, '1986-12-04', '09213622446', '71 Sampaguita Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0112', 'Yellow', '-', 'ronaldo59'],
    ['DRV-0090', 'Vicente Cruz', 25, '2002-07-28', '09638470249', '189 Aguinaldo Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0006', 'Brown', '-', 'vicente65'],
    ['DRV-0091', 'Estelita Castillo', 51, '1976-04-22', '09932176541', '230 Aguinaldo Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0249', 'Green', '-', 'estelita90'],
    ['DRV-0092', 'Maria Salazar', 52, '1975-08-15', '09096825067', '233 Mabini Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0040', 'Pink', '-', 'maria30'],
    ['DRV-0093', 'Josefina Jimenez', 47, '1980-02-28', '09978378058', '88 Aguinaldo Street, Poblacion, Bocaue, Bulacan', 'Poblacion', '0760', 'Maroon', '-', 'josefina97'],
    ['DRV-0094', 'Ignacio Morales', 32, '1995-12-20', '09504466001', '108 Maligaya Street, Sulucan, Bocaue, Bulacan', 'Sulucan', '0431', 'Orange', '-', 'ignacio23'],
    ['DRV-0095', 'Leonora Rivera', 50, '1977-09-08', '09673221378', '185 Sampaguita Street, Sulucan, Bocaue, Bulacan', 'Sulucan', '0987', 'Blue', '-', 'leonora91'],
    ['DRV-0096', 'Lorenzo Ramirez', 43, '1984-09-21', '09275346462', '124 Rizal Street, Sulucan, Bocaue, Bulacan', 'Sulucan', '0825', 'Pink', '-', 'lorenzo93'],
    ['DRV-0097', 'Carmen Domingo', 55, '1972-08-14', '09915246019', '232 Luna Street, Sulucan, Bocaue, Bulacan', 'Sulucan', '0966', 'Blue', '-', 'carmen54'],
    ['DRV-0098', 'Filomena Santos', 25, '2002-04-03', '09449192327', '89 Sto. Nino Street, Sulucan, Bocaue, Bulacan', 'Sulucan', '0157', 'Brown', '-', 'filomena78'],
    ['DRV-0099', 'Justina Estrada', 63, '1964-02-22', '09843738171', '143 Mabini Street, Taal, Bocaue, Bulacan', 'Taal', '0271', 'Green', '-', 'justina27'],
    ['DRV-0100', 'Wilfredo Diaz', 60, '1967-01-16', '09727372890', '42 San Jose Street, Taal, Bocaue, Bulacan', 'Taal', '0329', 'Maroon', '-', 'wilfredo73'],
    ['DRV-0101', 'Lourdes Ramirez', 48, '1979-07-14', '09125009051', '131 Del Pilar Street, Taal, Bocaue, Bulacan', 'Taal', '0497', 'Blue', '-', 'lourdes63'],
    ['DRV-0102', 'Rogelio Corpuz', 48, '1979-06-16', '09883465648', '14 San Jose Street, Taal, Bocaue, Bulacan', 'Taal', '0300', 'Orange', '-', 'rogelio61'],
    ['DRV-0103', 'Marites Morales', 42, '1985-09-12', '09371535429', '250 Roxas Avenue, Taal, Bocaue, Bulacan', 'Taal', '0090', 'Red', '-', 'marites80'],
    ['DRV-0104', 'Amado Medina', 28, '1999-07-10', '09100379088', '157 Aguinaldo Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0632', 'Blue', '-', 'amado99'],
    ['DRV-0105', 'Honorato De Guzman', 30, '1997-04-02', '09721206105', '224 San Jose Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0143', 'Pink', '-', 'honorato43'],
    ['DRV-0106', 'Carmen Fernandez', 51, '1976-01-11', '09069907980', '212 Mabini Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0793', 'Green', '-', 'carmen61'],
    ['DRV-0107', 'Severino Ocampo', 25, '2002-11-13', '09992768117', '55 Maligaya Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0156', 'Red', '-', 'severino11'],
    ['DRV-0108', 'Rolando Garcia', 38, '1989-02-05', '09704937205', '199 Magsaysay Avenue, Tambobong, Bocaue, Bulacan', 'Tambobong', '0731', 'Blue', '-', 'rolando20'],
    ['DRV-0109', 'Lourdes Batista', 60, '1967-12-16', '09740000091', '100 Malvar Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0320', 'Purple', '-', 'lourdes72'],
    ['DRV-0110', 'Isabelo Bautista', 45, '1982-06-19', '09772215267', '99 Sto. Nino Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0806', 'Maroon', '-', 'isabelo82'],
    ['DRV-0111', 'Marites Gonzales', 42, '1985-01-20', '09959029496', '250 Maligaya Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0253', 'Green', '-', 'marites58'],
    ['DRV-0112', 'Isabelo Lacson', 39, '1988-08-10', '09054462904', '214 Bonifacio Street, Tambobong, Bocaue, Bulacan', 'Tambobong', '0832', 'Purple', '-', 'isabelo80'],
    ['DRV-0113', 'Cesar Vasquez', 30, '1997-09-18', '09763349067', '182 Sampaguita Street, Turo, Bocaue, Bulacan', 'Turo', '0261', 'Purple', '-', 'cesar59'],
    ['DRV-0114', 'Teodoro Vasquez', 30, '1997-09-26', '09513698485', '123 Burgos Street, Turo, Bocaue, Bulacan', 'Turo', '0604', 'Brown', '-', 'teodoro34'],
    ['DRV-0115', 'Rodolfo Fernandez', 43, '1984-06-19', '09956823075', '222 Mabini Street, Turo, Bocaue, Bulacan', 'Turo', '0648', 'Maroon', '-', 'rodolfo29'],
    ['DRV-0116', 'Alexis Nunez', 26, '2001-06-09', '09890103979', '146 Sampaguita Street, Turo, Bocaue, Bulacan', 'Turo', '0268', 'Pink', '-', 'alexis67'],
    ['DRV-0117', 'Godofredo Nunez', 33, '1994-05-27', '09053261000', '143 Luna Street, Turo, Bocaue, Bulacan', 'Turo', '0892', 'Maroon', '-', 'godofredo18'],
    ['DRV-0118', 'Honorato Batista', 50, '1977-02-23', '09145931862', '115 San Jose Street, Turo, Bocaue, Bulacan', 'Turo', '0164', 'Orange', '-', 'honorato38'],
    ['DRV-0119', 'Teresita Cruz', 41, '1986-06-02', '09800487012', '82 Sto. Nino Street, Turo, Bocaue, Bulacan', 'Turo', '0694', 'Pink', '-', 'teresita66'],
    ['DRV-0120', 'Danilo Rodriguez', 45, '1982-06-09', '09615762732', '235 Roxas Avenue, Turo, Bocaue, Bulacan', 'Turo', '0914', 'Red', '-', 'danilo34'],
    ['DRV-0121', 'Manuel Villanueva', 39, '1988-02-20', '09527160175', '83 San Jose Street, Turo, Bocaue, Bulacan', 'Turo', '0240', 'Maroon', '-', 'manuel56'],
    ['DRV-0122', 'Arnel Pascual', 39, '1988-12-02', '09278272466', '64 Bonifacio Street, Turo, Bocaue, Bulacan', 'Turo', '0027', 'Pink', '-', 'arnel47'],
    ['DRV-0123', 'Rosario Salazar', 56, '1971-02-11', '09771280387', '214 Malvar Street, Wakas, Bocaue, Bulacan', 'Wakas', '0123', 'Pink', '-', 'rosario56'],
    ['DRV-0124', 'Pablo Salazar', 40, '1987-04-04', '09646204207', '207 Burgos Street, Wakas, Bocaue, Bulacan', 'Wakas', '0350', 'White', '-', 'pablo10'],
    ['DRV-0125', 'Gaudencio Gonzales', 36, '1991-06-14', '09063492228', '198 Sampaguita Street, Wakas, Bocaue, Bulacan', 'Wakas', '0729', 'Blue', '-', 'gaudencio20'],
    ['DRV-0126', 'Rodolfo Nunez', 56, '1971-05-06', '09329394301', '178 Magsaysay Avenue, Wakas, Bocaue, Bulacan', 'Wakas', '0533', 'Green', '-', 'rodolfo17'],
    ['DRV-0127', 'Efren Manalo', 46, '1981-05-27', '09710672432', '145 San Jose Street, Wakas, Bocaue, Bulacan', 'Wakas', '0376', 'Red', '-', 'efren57'],
    ['DRV-0128', 'Estelita Nunez', 25, '2002-06-17', '09781153569', '193 Maligaya Street, Wakas, Bocaue, Bulacan', 'Wakas', '0063', 'Pink', '-', 'estelita73'],
    ['DRV-0129', 'Severino Morales', 26, '2001-09-26', '09820313922', '27 Malvar Street, Wakas, Bocaue, Bulacan', 'Wakas', '0257', 'White', '-', 'severino13'],
    ['DRV-0130', 'Jose Torres', 37, '1990-05-01', '09997837151', '184 Bonifacio Street, Wakas, Bocaue, Bulacan', 'Wakas', '0047', 'Pink', '-', 'jose73'],
];

$inserted = 0;
$errors = 0;

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare(
        "INSERT INTO drivers (driver_id, name, age, birthday, contact_no, address, barangay_id, body_no, color_id, renewal_status, password)
         VALUES (:driver_id, :name, :age, :birthday, :contact_no, :address,
                 (SELECT barangay_id FROM barangays WHERE name = :barangay),
                 :body_no,
                 (SELECT color_id FROM colors WHERE name = :color),
                 :renewal_status, :password)"
    );
    
    foreach ($drivers as $driver) {
        try {
            $stmt->execute([
                'driver_id' => $driver[0],
                'name' => $driver[1],
                'age' => $driver[2],
                'birthday' => $driver[3],
                'contact_no' => $driver[4],
                'address' => $driver[5],
                'barangay' => $driver[6],
                'body_no' => $driver[7],
                'color' => $driver[8],
                'renewal_status' => $driver[9],
                'password' => $driver[10]
            ]);
            $inserted++;
            echo "<p class='success'>✓ Inserted: {$driver[0]} - {$driver[1]}</p>";
            flush();
        } catch (Exception $e) {
            $errors++;
            echo "<p class='error'>✗ Failed: {$driver[0]} - {$e->getMessage()}</p>";
            flush();
        }
    }
    
    $pdo->commit();
    
    echo '<hr>';
    echo "<h2 class='success'>✅ Seeding Complete!</h2>";
    echo "<p><strong>Total inserted:</strong> $inserted drivers</p>";
    echo "<p><strong>Errors:</strong> $errors</p>";
    echo '<hr>';
    echo '<p class="info"><strong>IMPORTANT:</strong> Delete this file (seed_drivers.php) for security!</p>';
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p class='error'><strong>Transaction failed:</strong> {$e->getMessage()}</p>";
    echo "<p class='error'>All changes have been rolled back.</p>";
}

echo '</body></html>';
