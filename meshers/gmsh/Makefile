CXX ?= g++
CXXFLAGS ?= -O3 -std=c++17
TARGET := mesh_data_cpp
SRC := mesh_data.cpp

.PHONY: all clean

all: $(TARGET)

$(TARGET): $(SRC)
	@if command -v pkg-config >/dev/null 2>&1 && pkg-config --exists gmsh; then \
		echo "Building $(TARGET) with pkg-config gmsh flags"; \
		$(CXX) $(CXXFLAGS) -o $@ $< $$(pkg-config --cflags --libs gmsh); \
	elif command -v gmsh-config >/dev/null 2>&1; then \
		echo "Building $(TARGET) with gmsh-config flags"; \
		$(CXX) $(CXXFLAGS) -o $@ $< $$(gmsh-config --cflags --libs); \
	else \
		echo "Building $(TARGET) with fallback gmsh include/lib paths"; \
		$(CXX) $(CXXFLAGS) -o $@ $< -I/usr/include -L/usr/lib/x86_64-linux-gnu -Wl,-rpath,/usr/lib/x86_64-linux-gnu -lgmsh; \
	fi

clean:
	rm -f $(TARGET)
