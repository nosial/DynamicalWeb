all: target/debug/net.nosial.dynamicalweb.ncc target/release/net.nosial.dynamicalweb.ncc
target/debug/net.nosial.dynamicalweb.ncc:
	ncc build --configuration debug --log-level debug
target/release/net.nosial.dynamicalweb.ncc:
	ncc build --configuration release --log-level debug


clean:
	rm -f target/debug/net.nosial.dynamicalweb.ncc
	rm -f target/release/net.nosial.dynamicalweb.ncc

.PHONY: all install clean