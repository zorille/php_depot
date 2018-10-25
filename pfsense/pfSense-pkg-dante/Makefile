# $FreeBSD$

PORTNAME=	pfSense-pkg-dante
PORTVERSION=	0.2
CATEGORIES=	sysutils
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	zorille@free.fr
COMMENT=	pfSense package Dante Socks

LICENSE=	APACHE20

RUN_DEPENDS=	${LOCALBASE}/sbin/sockd:net/dante

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www/dante
	${MKDIR} ${STAGEDIR}${PREFIX}/www/shortcuts
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/dante.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/dante/dante_clients_mgmt.php \
		${STAGEDIR}${PREFIX}/www/dante
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/dante/dante_socks_mgmt.php \
		${STAGEDIR}${PREFIX}/www/dante
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/dante/dante_routes_mgmt.php \
		${STAGEDIR}${PREFIX}/www/dante
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/dante/dante_view_logs.php \
		${STAGEDIR}${PREFIX}/www/dante
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/shortcuts/pkg_dante.inc \
		${STAGEDIR}${PREFIX}/www/shortcuts
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/dante.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/dante.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
