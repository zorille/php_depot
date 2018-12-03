# $FreeBSD$

PORTNAME=	pfSense-pkg-SockdIOPS
PORTVERSION=	0.1
CATEGORIES=	sysutils
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	zorille@free.fr
COMMENT=	pfSense package Dante Socks

LICENSE=	APACHE20

RUN_DEPENDS=	${LOCALBASE}/sbin/sockd:net/dante \
				${LOCALBASE}/bin/dot:graphics/graphviz

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${MKDIR} ${STAGEDIR}${PREFIX}/www/SockdIOPS
	${MKDIR} ${STAGEDIR}${PREFIX}/www/shortcuts
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Assign.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/AttributeBag.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/AttributeSet.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/BaseInstruction.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Digraph.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/DirectedEdge.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Edge.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Graph.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/InstructionInterface.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Node.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/RawText.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/Subgraph.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/SockdIOPS/SockdIOPS_clients_mgmt.php \
		${STAGEDIR}${PREFIX}/www/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/SockdIOPS/SockdIOPS_socks_mgmt.php \
		${STAGEDIR}${PREFIX}/www/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/SockdIOPS/SockdIOPS_routes_mgmt.php \
		${STAGEDIR}${PREFIX}/www/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/SockdIOPS/SockdIOPS_view_logs.php \
		${STAGEDIR}${PREFIX}/www/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/SockdIOPS/SockdIOPS_view_network.php \
		${STAGEDIR}${PREFIX}/www/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/shortcuts/pkg_SockdIOPS.inc \
		${STAGEDIR}${PREFIX}/www/shortcuts
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/SockdIOPS.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/SockdIOPS.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/SockdIOPS/network.inc \
		${STAGEDIR}${PREFIX}/pkg/SockdIOPS
	${INSTALL_DATA} ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
