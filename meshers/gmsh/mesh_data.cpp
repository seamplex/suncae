#include <gmsh.h>
#include <iostream>
#include <fstream>
#include <vector>
#include <set>
#include <array>
#include <algorithm>
#include <string>
#include <map>

using Line2 = std::array<int, 2>;
using Line3 = std::array<int, 3>;

std::string format_nodes(const std::vector<double>& coords) {
    std::string out;
    size_t n = coords.size() / 3;
    for (size_t i = 0; i < n; ++i) {
        out += std::to_string(coords[3*i+0]) + " " +
               std::to_string(coords[3*i+1]) + " " +
               std::to_string(coords[3*i+2]) + "  ";
    }
    return out;
}

int main(int argc, char** argv) {
    if (argc < 3) {
        std::cerr << "need mesh hash and dir in the command line" << std::endl;
        return 1;
    }

    std::string mesh_file = std::string(argv[2]) + "/" + argv[1] + ".msh";
    std::ifstream infile(mesh_file);
    if (!infile.good()) {
        std::cerr << "mesh file does not exist" << std::endl;
        return 1;
    }
    infile.close();

    std::cout << "1" << std::endl;
    gmsh::initialize();
    gmsh::option::setNumber("General.Terminal", 0);
    gmsh::open(mesh_file);

    std::map<std::string, std::string> mesh;

    // Nodes
    std::vector<std::size_t> nodeTags;
    std::vector<double> nodeCoords, parametricCoords;
    gmsh::model::mesh::getNodes(nodeTags, nodeCoords, parametricCoords);

    mesh["nodes"] = format_nodes(nodeCoords);

    // Surface edges (lines)
    std::set<Line2> lines2;
    std::set<Line3> lines3;
    std::string surfaces_edges_set;

    std::vector<int> elementTypes;
    std::vector<std::vector<std::size_t>> elementTags, elementNodeTags;

    // 2 = surface elements
    gmsh::model::mesh::getElements(elementTypes, elementTags, elementNodeTags, 2);

    for (size_t i = 0; i < elementTypes.size(); ++i) {
        int type = elementTypes[i];
        const auto& nodes = elementNodeTags[i];
        // 3-node triangles (type==2) or possibly 6-node (type==9)
        size_t j = 0;
        if (type == 2) {
            // Linear triangle: 3 nodes per element
            for (size_t e = 0; e < elementTags[i].size(); ++e) {
                std::array<int,2> l1 = {static_cast<int>(nodes[j+0])-1, static_cast<int>(nodes[j+1])-1};
                std::array<int,2> l2 = {static_cast<int>(nodes[j+1])-1, static_cast<int>(nodes[j+2])-1};
                std::array<int,2> l3 = {static_cast<int>(nodes[j+2])-1, static_cast<int>(nodes[j+0])-1};
                std::sort(l1.begin(), l1.end());
                std::sort(l2.begin(), l2.end());
                std::sort(l3.begin(), l3.end());
                lines2.insert(l1);
                lines2.insert(l2);
                lines2.insert(l3);
                j += 3;
            }
        } else if (type == 9) {
            // Quadratic triangle: 6 nodes per element
            for (size_t e = 0; e < elementTags[i].size(); ++e) {
                std::array<int,3> l1 = {static_cast<int>(nodes[j+0])-1, static_cast<int>(nodes[j+3])-1, static_cast<int>(nodes[j+1])-1};
                std::array<int,3> l2 = {static_cast<int>(nodes[j+1])-1, static_cast<int>(nodes[j+4])-1, static_cast<int>(nodes[j+2])-1};
                std::array<int,3> l3 = {static_cast<int>(nodes[j+2])-1, static_cast<int>(nodes[j+5])-1, static_cast<int>(nodes[j+0])-1};
                std::sort(l1.begin(), l1.end());
                std::sort(l2.begin(), l2.end());
                std::sort(l3.begin(), l3.end());
                lines3.insert(l1);
                lines3.insert(l2);
                lines3.insert(l3);
                j += 6;
            }
        }
    }

    for (const auto& l : lines2) {
        surfaces_edges_set += std::to_string(l[0]) + " " + std::to_string(l[1]) + " -1 ";
    }
    for (const auto& l : lines3) {
        surfaces_edges_set += std::to_string(l[0]) + " " + std::to_string(l[1]) + " " + std::to_string(l[2]) + " -1 ";
    }
    mesh["surfaces_edges_set"] = surfaces_edges_set;

    std::cout << "2" << std::endl;

    // Surface faces, one per each physical group
    std::map<int, std::string> surfaces_faces_set;
    std::vector<std::pair<int,int>> physicals;
    gmsh::model::getPhysicalGroups(physicals);

    for (const auto& physical : physicals) {
        int dim = physical.first;
        int physical_tag = physical.second;
        if (dim == 2) {
            std::string faces;
            std::vector<int> entities;
            gmsh::model::getEntitiesForPhysicalGroup(dim, physical_tag, entities);
            for (int entity : entities) {
                std::vector<int> types;
                std::vector<std::vector<std::size_t>> tags, nodetags;
                gmsh::model::mesh::getElements(types, tags, nodetags, dim, entity);
                for (size_t i = 0; i < types.size(); ++i) {
                    int type = types[i];
                    for (size_t j = 0; j < tags[i].size(); ++j) {
                        if (type == 2) {
                            // 3-node triangle
                            faces += std::to_string(static_cast<int>(nodetags[i][j*3+0])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*3+1])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*3+2])-1) + " ";
                        } else if (type == 9) {
                            // 6-node triangle
                            faces += std::to_string(static_cast<int>(nodetags[i][j*6+0])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+3])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+5])-1) + " ";
                            faces += std::to_string(static_cast<int>(nodetags[i][j*6+1])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+4])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+3])-1) + " ";
                            faces += std::to_string(static_cast<int>(nodetags[i][j*6+2])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+5])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+4])-1) + " ";
                            faces += std::to_string(static_cast<int>(nodetags[i][j*6+3])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+4])-1) + " " +
                                     std::to_string(static_cast<int>(nodetags[i][j*6+5])-1) + " ";
                        }
                    }
                }
            }
            surfaces_faces_set[physical_tag] = faces;
        }
    }

    mesh["surfaces_faces_set"] = "";
    for (const auto& kv : surfaces_faces_set) {
        mesh["surfaces_faces_set"] += "\"" + std::to_string(kv.first) + "\": \"" + kv.second + "\", ";
    }

    std::cout << "3" << std::endl;

    gmsh::finalize();

    // Output as JSON (simple, not using a JSON lib)
    std::ofstream out(std::string(argv[2]) + "/" + argv[1] + "-data.json");
    out << "{\n";
    out << "\"nodes\": \"" << mesh["nodes"] << "\",\n";
    out << "\"surfaces_edges_set\": \"" << mesh["surfaces_edges_set"] << "\",\n";
    out << "\"surfaces_faces_set\": {" << mesh["surfaces_faces_set"] << "}\n";
    out << "}" << std::endl;
    out.close();

    return 0;
}
